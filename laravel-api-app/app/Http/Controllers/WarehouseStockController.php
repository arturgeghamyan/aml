<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseStockController extends Controller
{
    public function update(Request $request, Warehouse $warehouse)
    {
        $user = $request->user();
        if ($user->role !== 'employee') {
            abort(403, 'Only employees can update stock.');
        }

        $data = $request->validate([
            'stock_amount' => 'required|integer|min:0',
        ]);

        $warehouse->update(['stock_amount' => $data['stock_amount']]);

        return ['warehouse' => $warehouse];
    }
}
