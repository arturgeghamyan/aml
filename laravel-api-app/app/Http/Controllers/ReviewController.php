<?php

namespace App\Http\Controllers;

use App\Models\OrderTable;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            abort(403, 'Only customers can add reviews.');
        }

        $data = $request->validate([
            'order_id' => 'required|integer',
            'order_item_id' => 'required|integer',
            'review_rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string',
            'title' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data, $user) {
            $order = OrderTable::with(['items', 'paymentTransaction.payment'])
                ->where('order_id', $data['order_id'])
                ->where('customer_id', $user->id)
                ->firstOrFail();

            $paymentStatus = $order->paymentTransaction->payment->payment_status ?? 'pending';
            if ($paymentStatus !== 'paid') {
                abort(403, 'Order must be paid before reviewing.');
            }

            $item = $order->items->firstWhere('order_item_id', $data['order_item_id']);
            if (!$item) {
                abort(404, 'Order item not found for this order.');
            }

            if (Review::where([
                'order_id' => $data['order_id'],
                'order_item_id' => $data['order_item_id'],
                'user_id' => $user->id,
            ])->exists()) {
                abort(422, 'You already reviewed this item.');
            }

            $review = Review::create([
                'order_id' => $data['order_id'],
                'order_item_id' => $data['order_item_id'],
                'user_id' => $user->user_id,
                'review_rating' => $data['review_rating'],
                'comment' => $data['comment'] ?? null,
                'title' => $data['title'] ?? null,
            ]);

            return ['review' => $review];
        });
    }

    public function productReviews(Product $product)
    {
        $reviews = Review::with(['user'])
            ->whereHas('orderItem', function ($q) use ($product) {
                $q->where('product_id', $product->product_id);
            })
            ->latest('created_at')
            ->get();

        return ['reviews' => $reviews];
    }
}
