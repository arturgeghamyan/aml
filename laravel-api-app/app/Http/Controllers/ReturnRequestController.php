<?php

namespace App\Http\Controllers;

use App\Models\OrderTable;
use App\Models\Refund;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnRequestController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            abort(403, 'Only customers can request returns.');
        }

        $data = $request->validate([
            'order_id' => 'required|integer',
            'order_item_id' => 'required|integer',
            'reason' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data, $user) {
            $order = OrderTable::with(['items', 'paymentTransaction.payment'])
                ->where('order_id', $data['order_id'])
                ->where('customer_id', $user->id)
                ->firstOrFail();

            $paymentStatus = $order->paymentTransaction->payment->payment_status ?? 'pending';
            if ($paymentStatus !== 'paid') {
                abort(403, 'Order must be paid before requesting a return.');
            }

            $item = $order->items->firstWhere('order_item_id', $data['order_item_id']);
            if (!$item) {
                abort(404, 'Order item not found.');
            }

            if (ReturnRequest::where([
                'order_id' => $data['order_id'],
                'order_item_id' => $data['order_item_id'],
            ])->exists()) {
                abort(422, 'A return request already exists for this item.');
            }

            $returnRequest = ReturnRequest::create([
                'order_id' => $data['order_id'],
                'order_item_id' => $data['order_item_id'],
                'request_status' => 'pending',
                'reason' => $data['reason'] ?? null,
            ]);

            return ['return_request' => $returnRequest];
        });
    }

    public function index()
    {
        $user = request()->user();
        if ($user->role !== 'employee') {
            abort(403, 'Only employees can view return requests.');
        }

        $requests = ReturnRequest::with(['refund'])
            ->latest('requested_at')
            ->get();

        $requests->each(function ($req) {
            $item = \App\Models\OrderItem::with('product')
                ->where('order_id', $req->order_id)
                ->where('order_item_id', $req->order_item_id)
                ->first();
            $req->setRelation('orderItem', $item);
        });

        return $requests;
    }

    public function decide(Request $request, ReturnRequest $returnRequest)
    {
        $user = $request->user();
        if ($user->role !== 'employee') {
            abort(403, 'Only employees can process return requests.');
        }

        $data = $request->validate([
            'request_status' => 'required|in:accepted,rejected',
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($returnRequest, $data) {
            $returnRequest->update([
                'request_status' => $data['request_status'],
                'reason' => $data['reason'] ?? $returnRequest->reason,
            ]);

            $refund = null;
            if ($data['request_status'] === 'accepted') {
                $defaultAmount = $returnRequest->orderItem?->quantity * $returnRequest->orderItem?->unit_price;
                $refund = Refund::updateOrCreate(
                    ['return_request_id' => $returnRequest->return_request_id],
                    [
                        'amount' => $data['amount'] ?? $defaultAmount ?? 0,
                        'reason' => $data['reason'] ?? $returnRequest->reason,
                    ]
                );
                $refund->loadMissing('returnRequest');
            } else {
                $returnRequest->refund()?->delete();
            }

            $returnRequest->load('refund');
            $item = \App\Models\OrderItem::with('product')
                ->where('order_id', $returnRequest->order_id)
                ->where('order_item_id', $returnRequest->order_item_id)
                ->first();
            $returnRequest->setRelation('orderItem', $item);

            return [
                'return_request' => $returnRequest->load(['refund', 'orderItem.product']),
                'refund' => $refund,
            ];
        });
    }
}
