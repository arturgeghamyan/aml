<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;

class WarehouseController extends Controller
{
    public function index()
    {
        return ['warehouses' => Warehouse::all()];
    }
}
