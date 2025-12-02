<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'review';
    protected $primaryKey = 'review_id';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'user_id',
        'review_rating',
        'comment',
        'title',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'order_item_id')
            ->whereColumn('review.order_id', 'order_item.order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
