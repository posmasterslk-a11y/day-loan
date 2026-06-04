<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with(['loans.schedules' => function ($q) {
            $q->where('status', 'Missed');
        }])->orderBy('id', 'desc')->get();
        
        return response()->json($customers->map(function ($c) {
            $maxArrearsDays = 0;
            foreach ($c->loans as $loan) {
                $arrearsCount = $loan->schedules->count();
                if ($arrearsCount > $maxArrearsDays) {
                    $maxArrearsDays = $arrearsCount;
                }
            }

            return [
                'db_id' => $c->id,
                'id' => $c->customer_id, 
                'name' => $c->full_name,
                'nic' => $c->nic,
                'email' => $c->email,
                'location' => $c->address,
                'address' => $c->address,
                'phone' => $c->phone,
                'whatsapp' => $c->whatsapp,
                'dob' => $c->dob,
                'gender' => $c->gender,
                'business_type' => $c->business_type,
                'monthly_income' => $c->monthly_income,
                'status' => $c->status ? strtolower($c->status) : 'active',
                'avatar' => ['src' => $c->photo_path ? asset('storage/' . $c->photo_path) : ''],
                'is_7_days_arrears' => $maxArrearsDays >= 7
            ];
        }));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nic' => 'required|string|unique:customers,nic',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string',
            'phone' => 'required|string',
            'whatsapp' => 'nullable|string',
            'address' => 'nullable|string',
            'business_type' => 'nullable|string',
            'monthly_income' => 'nullable|numeric',
            'photo' => 'nullable|image|max:2048',
            'nic_front' => 'nullable|image|max:2048',
            'nic_back' => 'nullable|image|max:2048',
            'officer_id' => 'nullable|exists:users,id'
        ]);

        $data = $request->except(['photo', 'nic_front', 'nic_back']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('customers/photos', 'public');
        }
        if ($request->hasFile('nic_front')) {
            $data['nic_front_path'] = $request->file('nic_front')->store('customers/nic', 'public');
        }
        if ($request->hasFile('nic_back')) {
            $data['nic_back_path'] = $request->file('nic_back')->store('customers/nic', 'public');
        }

        $customer = Customer::create($data);

        return response()->json([
            'message' => 'Customer created successfully!',
            'customer' => $customer
        ], 201);
    }

    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        
        $loans = \App\Models\Loan::with(['schedules', 'payments' => function($q) {
            $q->with('officer')->orderBy('created_at', 'asc');
        }])->where('customer_id', $customer->id)
          ->orderBy('created_at', 'desc')
          ->get();

        return response()->json([
            'customer' => $customer,
            'loans' => $loans
        ]);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nic' => 'required|string|unique:customers,nic,' . $customer->id,
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'dob' => 'nullable|date',
            'gender' => 'nullable|string',
            'phone' => 'required|string',
            'whatsapp' => 'nullable|string',
            'address' => 'nullable|string',
            'business_type' => 'nullable|string',
            'monthly_income' => 'nullable|numeric',
            'status' => 'required|in:active,inactive,pending'
        ]);

        $customer->update([
            'full_name' => $request->full_name,
            'nic' => $request->nic,
            'email' => $request->email,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'phone' => $request->phone,
            'whatsapp' => $request->whatsapp,
            'address' => $request->address,
            'business_type' => $request->business_type,
            'monthly_income' => $request->monthly_income,
            'status' => ucfirst(strtolower($request->status)), // DB stores as Active, Inactive, etc
        ]);

        return response()->json([
            'message' => 'Customer updated successfully!',
            'customer' => $customer
        ]);
    }
}
