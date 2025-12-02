<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $table = 'warehouse';
    protected $primaryKey = 'warehouse_id';
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'warehouse_name',
        'street',
        'city',
        'zip_code',
        'stock_amount',
    ];
}
