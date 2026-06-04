<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::with(['role', 'branch'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);
        
        $data['password'] = bcrypt($data['password']);
        
        $user = User::create($data);
        return response()->json($user->load(['role', 'branch']), 201);
    }
    
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:6',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        
        $user->save();

        return response()->json($user->load(['role', 'branch']));
    }
}
