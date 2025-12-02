<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $table = 'return_request';
    protected $primaryKey = 'return_request_id';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'request_status',
        'reason',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'order_item_id');
    }

    public function refund()
    {
        return $this->hasOne(Refund::class, 'return_request_id', 'return_request_id');
    }
}
