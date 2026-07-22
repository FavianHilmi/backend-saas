<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Traits\APIResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use APIResponse;

    public function register(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $company = Company::create([
                'name' => $validated['company_name'],
            ]);

            return User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
                'company_id' => $company->id,
            ]);
        });

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user->load('company'),
            'token' => $token,
        ], 'Registration successful', 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user->load('company'),
            'token' => $token,
        ], 'Berhasil Login');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Berhasil Logout');
    }

    public function registerMember(Request $request)
    {

        if (auth()->user()->role !== 'admin') {
            return $this->errorResponse('Hanya admin yang dapat menambahkan member', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'member',
            'company_id' => auth()->user()->company_id,
        ]);

        return $this->successResponse($user, 'Member berhasil ditambahkan', 201);
    }
}
