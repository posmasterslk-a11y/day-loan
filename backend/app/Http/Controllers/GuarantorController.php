<?php

namespace App\Http\Controllers;

use App\Models\Guarantor;
use Illuminate\Http\Request;

class GuarantorController extends Controller
{
    public function index()
    {
        return response()->json(Guarantor::orderBy('id', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nic' => 'required|string|unique:guarantors,nic',
            'phone' => 'required|string',
            'address' => 'nullable|string',
            'relationship' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['photo']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('guarantors/photos', 'public');
        }

        $guarantor = Guarantor::create($data);

        return response()->json([
            'message' => 'Guarantor created successfully!',
            'guarantor' => $guarantor
        ], 201);
    }
    
    public function destroy($id)
    {
        Guarantor::findOrFail($id)->delete();
        return response()->json(['message' => 'Guarantor deleted']);
    }
}
