<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $table = 'refund';
    protected $primaryKey = 'refund_id';
    public $timestamps = false;

    protected $fillable = [
        'return_request_id',
        'amount',
        'reason',
    ];

    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class, 'return_request_id', 'return_request_id');
    }
}
