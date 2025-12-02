<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_item';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'unit_price',
    ];

    public function order()
    {
        return $this->belongsTo(OrderTable::class, 'order_id', 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'order_item_id', 'order_item_id')
            ->where('order_id', $this->order_id);
    }

    public function returnRequest()
    {
        return $this->hasOne(ReturnRequest::class, 'order_item_id', 'order_item_id')
            ->where('order_id', $this->order_id);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }
}
