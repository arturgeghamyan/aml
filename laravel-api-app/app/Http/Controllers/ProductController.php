<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show']),
        ];
    }

    public function index()
    {
        $query = Product::with(['category', 'seller'])->latest();

        $user = auth('sanctum')->user();
        if ($user?->role === 'employee') {
            // employees see everything
        } elseif ($user?->role === 'seller') {
            // sellers see only their own products (any status)
            $query->where('user_id', $user->id);
        } else {
            // customers and guests see only active products
            $query->where('product_status', 'active');
        }

        $search = request()->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('product_description', 'like', "%{$search}%")
                    ->orWhere('product_brand', 'like', "%{$search}%");
            });
        }

        if ($categoryId = request()->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $priceMin = request()->input('price_min');
        $priceMax = request()->input('price_max');

        if ($priceMin !== null && $priceMin !== '') {
            $query->where('product_price', '>=', $priceMin);
        }

        if ($priceMax !== null && $priceMax !== '') {
            $query->where('product_price', '<=', $priceMax);
        }

        return $query->paginate(request()->integer('paginate', 9));
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'seller') {
            abort(403, 'Only sellers can create products.');
        }

        $fields = $request->validate([
            'product_name' => 'required|string|max:150',
            'product_description' => 'nullable|string',
            'product_price' => 'required|numeric|min:0',
            'product_status' => 'nullable|in:active,inactive',
            'product_brand' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $fields['user_id'] = $request->user()->id;
        $fields['product_status'] = 'inactive';

        $product = Product::create($fields)->load(['category', 'seller']);

        return ['product' => $product];
    }

    public function show(Product $product)
    {
        $user = auth('sanctum')->user();
        $isEmployee = $user && $user->role === 'employee';
        $isSellerOwner = $user && $user->role === 'seller' && $user->id === $product->user_id;

        // if (!$isEmployee && !$isSellerOwner && $product->product_status !== 'active') {
        //     abort(404);
        // }

        return ['product' => $product->load(['category', 'seller'])];
    }

    public function update(Request $request, Product $product)
    {
        $user = $request->user();
        $isSellerOwner = $user->role === 'seller' && $user->id === $product->user_id;
        $isEmployee = $user->role === 'employee';

        if (!($isSellerOwner || $isEmployee)) {
            abort(403, 'Unauthorized.');
        }

        if ($isEmployee) {
            $fields = $request->validate([
                'product_status' => 'required|in:active,inactive',
            ]);
            $product->update(['product_status' => $fields['product_status']]);
        } else {
            $fields = $request->validate([
                'product_name' => 'required|string|max:150',
                'product_description' => 'nullable|string',
                'product_price' => 'required|numeric|min:0',
                'product_brand' => 'nullable|string|max:100',
                'category_id' => 'nullable|exists:categories,id',
            ]);

            $product->update($fields);
        }

        return ['product' => $product->load(['category', 'seller'])];
    }

    public function destroy(Request $request, Product $product)
    {
        if ($request->user()->id !== $product->user_id) {
            abort(403, 'Unauthorized.');
        }

        $product->delete();

        return ['message' => 'Product deleted'];
    }

    public function approve(Request $request, Product $product)
    {
        if ($request->user()->role !== 'employee') {
            abort(403, 'Only employees can approve products.');
        }

        $product->update(['product_status' => 'active']);

        return ['product' => $product->load(['category', 'seller'])];
    }
}
