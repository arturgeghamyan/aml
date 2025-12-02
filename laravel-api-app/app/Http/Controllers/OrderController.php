<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\OrderTable;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReturnRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            abort(403, 'Only customers can place orders.');
        }

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:Credit Card,PayPal,Bank Transfer',
        ]);

        return DB::transaction(function () use ($data, $user) {
            $order = OrderTable::create(['customer_id' => $user->id]);

            $total = 0;
            foreach ($data['items'] as $index => $item) {
                $product = Product::where('product_id', $item['product_id'])
                    ->where('product_status', 'active')
                    ->firstOrFail();

                $unitPrice = $product->product_price;
                $quantity = $item['quantity'];
                $subtotal = $unitPrice * $quantity;
                $total += $subtotal;

                OrderItem::create([
                    'order_id' => $order->order_id,
                    'order_item_id' => $index + 1,
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);
            }

            $transactionId = Str::uuid()->toString();
            $transaction = PaymentTransaction::create([
                'transaction_id' => $transactionId,
                'order_id' => $order->order_id,
                'payment_method' => $data['payment_method'],
            ]);

            Payment::create([
                'transaction_id' => $transactionId,
                'payment_status' => 'pending',
                'amount' => $total,
            ]);

            $this->attachExtras($order);

            return [
                'order' => $order->load(['items.product', 'paymentTransaction.payment']),
                'total' => $total,
            ];
        });
    }

    public function pay(Request $request, OrderTable $order)
    {
        $user = $request->user();
        if ($user->role !== 'customer' || $order->customer_id !== $user->id) {
            abort(403, 'Only the customer who placed the order can pay.');
        }

        $paymentMethod = $request->validate([
            'payment_method' => 'required|in:Credit Card,PayPal,Bank Transfer',
        ])['payment_method'];

        return DB::transaction(function () use ($order, $paymentMethod) {
            $transaction = $order->paymentTransaction;

            if (!$transaction) {
                $transactionId = Str::uuid()->toString();
                $transaction = PaymentTransaction::create([
                    'transaction_id' => $transactionId,
                    'order_id' => $order->order_id,
                    'payment_method' => $paymentMethod,
                ]);
                Payment::create([
                    'transaction_id' => $transactionId,
                    'payment_status' => 'pending',
                    'amount' => $order->items()->selectRaw('SUM(quantity * unit_price) as total')->value('total') ?? 0,
                ]);
            }

            $payment = $transaction->payment;
            if ($payment && $payment->payment_status === 'paid') {
                return [
                    'order' => $order->load(['items.product', 'paymentTransaction.payment']),
                    'message' => 'Order already paid',
                ];
            }

            $payment->update([
                'payment_status' => 'paid',
                'payment_date' => now(),
            ]);

            $this->attachExtras($order);

            return [
                'order' => $order->load(['items.product', 'paymentTransaction.payment']),
                'message' => 'Payment simulated successfully',
            ];
        });
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            abort(403, 'Only customers can view their orders.');
        }

        return $user->orders()
            ->with(['items.product', 'paymentTransaction.payment'])
            ->latest('order_date')
            ->get()
            ->tap(function ($orders) {
                $orders->each(fn ($order) => $this->attachExtras($order));
            });
    }

    public function employeeOrders()
    {
        $user = request()->user();
        if ($user->role !== 'employee') {
            abort(403, 'Only employees can view orders.');
        }

        return OrderTable::with(['items.product', 'items.warehouse', 'paymentTransaction.payment'])
            ->latest('order_date')
            ->get()
            ->tap(function ($orders) {
                $orders->each(fn ($order) => $this->attachExtras($order));
            });
    }

    public function assignWarehouses(Request $request, OrderTable $order)
    {
        $user = $request->user();
        if ($user->role !== 'employee') {
            abort(403, 'Only employees can assign warehouses.');
        }

        $paymentStatus = $order->paymentTransaction->payment->payment_status ?? 'pending';
        if ($paymentStatus !== 'paid') {
            abort(403, 'Order must be paid before assigning warehouses.');
        }

        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouse,warehouse_id',
        ]);

        DB::transaction(function () use ($data, $order) {
            $newWarehouseId = (int) $data['warehouse_id'];
            $items = $order->items()->get(['order_item_id', 'warehouse_id', 'quantity']);

            $adjustments = [];
            foreach ($items as $item) {
                $qty = (int) $item->quantity;
                $prevWarehouseId = $item->warehouse_id;

                if ($prevWarehouseId && $prevWarehouseId !== $newWarehouseId) {
                    $adjustments[$prevWarehouseId] = ($adjustments[$prevWarehouseId] ?? 0) + $qty; // add back
                }

                if ($prevWarehouseId !== $newWarehouseId) {
                    $adjustments[$newWarehouseId] = ($adjustments[$newWarehouseId] ?? 0) - $qty; // subtract
                }
            }

            if (!empty($adjustments)) {
                $warehouses = Warehouse::whereIn('warehouse_id', array_keys($adjustments))->lockForUpdate()->get();

                foreach ($warehouses as $warehouse) {
                    $delta = $adjustments[$warehouse->warehouse_id] ?? 0;
                    $newStock = $warehouse->stock_amount + $delta;
                    if ($newStock < 0) {
                        abort(422, "Not enough stock in {$warehouse->warehouse_name}.");
                    }
                }

                foreach ($warehouses as $warehouse) {
                    $delta = $adjustments[$warehouse->warehouse_id] ?? 0;
                    $warehouse->update(['stock_amount' => $warehouse->stock_amount + $delta]);
                }
            }

            $order->items()->update(['warehouse_id' => $newWarehouseId]);
        });

        $order->load(['items.product', 'items.warehouse', 'paymentTransaction.payment']);
        $this->attachExtras($order);

        return ['order' => $order];
    }

    private function attachExtras(OrderTable $order): void
    {
        $order->loadMissing(['items.warehouse']);
        foreach ($order->items as $idx => $item) {
            if (empty($item->order_item_id)) {
                $item->order_item_id = $idx + 1;
            }
            $review = Review::where('order_id', $order->order_id)
                ->where('order_item_id', $item->order_item_id)
                ->where('user_id', $order->customer_id)
                ->first();

            $item->setRelation('review', $review);

            $returnRequest = ReturnRequest::with('refund')
                ->where('order_id', $order->order_id)
                ->where('order_item_id', $item->order_item_id)
                ->first();
            $item->setRelation('return_request', $returnRequest);
        }
    }
}
