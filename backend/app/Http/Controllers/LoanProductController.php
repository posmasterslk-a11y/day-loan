<?php

namespace App\Http\Controllers;

use App\Models\LoanProduct;
use Illuminate\Http\Request;

class LoanProductController extends Controller
{
    public function index()
    {
        return response()->json(LoanProduct::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|gte:min_amount',
            'interest_rate' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
        ]);

        $product = LoanProduct::create($data);
        return response()->json($product, 201);
    }
    
    public function destroy($id)
    {
        LoanProduct::findOrFail($id)->delete();
        return response()->json(['message' => 'Product deleted']);
    }
}
