<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'role' => 'nullable|in:customer,seller,employee',
        ]);

        $fields['role'] = $fields['role'] ?? 'customer';

        $user = User::create($fields);

        $token = $user->createToken($user->first_name);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

        /**
     * Send a new email verification notification.
     */
    public function verifyEmailNotification(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return response()->json(['status' => 'verification-link-sent']);
    }

    public function verifyEmail(Request $request)
    {
        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user && !$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            return response()->json(['message' => 'Email verified successfully.']);
        }

        return response()->json(['error' => 'Invalid or expired verification link.'], 400);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:user_table',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'errors' => [
                    'email' => ['The provided credentials are incorrect.']
                ]
            ];
            // return [
            //     'message' => 'The provided credentials are incorrect.' 
            // ];
        }

        $token = $user->createToken($user->first_name);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return [
            'message' => 'You are logged out.' 
        ];
    }
}
