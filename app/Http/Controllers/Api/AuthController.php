<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Services\AuditLogger;

class AuthController extends Controller
{
    /**
     * Admin login API
     */
    public function login(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            // Try to find user to log failed attempt
            $user = \App\Models\User::where('email', $request->email)->first();
            AuditLogger::log('Failed Login', 'Login Attempt Failed', 'Invalid login credentials.', null, null, $user ? $user->id : null);
            
            return response()->json([
                'message' => 'Invalid login credentials.'
            ], 401);
        }

        $user = Auth::user();

        // Check if the user has the SuperAdmin role
        if (!$user->hasRole('SuperAdmin')) {
            AuditLogger::log('Failed Login', 'Unauthorized Login Attempt', 'User lacks SuperAdmin role.', null, null, $user->id);
            return response()->json([
                'message' => 'Unauthorized. Only SuperAdmins can login.'
            ], 403);
        }

        // Generate token
        $token = $user->createToken('admin-token')->plainTextToken;

        AuditLogger::log('Admin Login', 'User Logged In', 'SuperAdmin login successful.', null, null, $user->id);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout API
     */
    public function logout(Request $request)
    {
        AuditLogger::log('Admin Logout', 'User Logged Out', 'Admin logout successful.');
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Change Password API
     */
    public function changePassword(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password does not match.'
            ], 400);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        $user->save();

        AuditLogger::log('Password Changed', 'User Password Changed', 'Admin changed their password.');

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully.'
        ]);
    }
}
