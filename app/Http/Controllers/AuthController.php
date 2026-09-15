<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function csrf(): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::ok(['csrf_token' => csrf_token()]);
    }

    public function me(Request $request): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::ok($request->user()?->toArray());
    }

    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:student,staff'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::fail($validator->errors()->first(), 422);
        }

        $user = User::create($validator->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return ApiResponse::ok($user->toArray(), 201);
    }

    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::fail($validator->errors()->first(), 422);
        }

        if (! Auth::attempt($validator->validated())) {
            return ApiResponse::fail('Email atau password salah.', 401);
        }

        $request->session()->regenerate();

        return ApiResponse::ok($request->user()->toArray());
    }

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::ok();
    }
}