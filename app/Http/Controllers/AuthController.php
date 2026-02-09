<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function checkEmail(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $exists = User::where('email', $request->email)->exists();
    
    return response()->json([
        'exists' => $exists
    ]);
}
public function login(Request $request){
    try {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email not found'
            ], 200);
        }

        if (!password_verify($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password'
            ], 200);
        }

        // Create token for authentication
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'postal_code' => $user->postal_code,
                'country' => $user->country,
                'is_admin' => $user->email === 'admin@gmail.com'
            ],
            'token' => $token
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Login failed. Please try again.',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function updateProfile(Request $request)
{
    try {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $request->user()->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->address = $request->address;
        $user->city = $request->city;
        $user->postal_code = $request->postal_code;
        $user->country = $request->country;
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to update profile',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function changePassword(Request $request)
{
    try {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Verify current password
        if (!\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Update password
        $user->password = \Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully'
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to change password',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function logout(Request $request)
{
    try {
        // Revoke the user's current token
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Logout successful'
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Logout failed',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function signup(Request $request)
{
    try {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Handle validation errors (including unique email)
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        // Handle other exceptions
        return response()->json([
            'message' => 'Failed to create user. Please try again.',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function users(Request $request)
{
    try {
        $users = User::select('id', 'name', 'email', 'phone', 'address', 'city', 'postal_code', 'country', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to fetch users',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
