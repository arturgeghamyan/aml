<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTable extends Model
{
    protected $table = 'order_table';
    protected $primaryKey = 'order_id';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'order_date',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    public function paymentTransaction()
    {
        return $this->hasOne(PaymentTransaction::class, 'order_id', 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
