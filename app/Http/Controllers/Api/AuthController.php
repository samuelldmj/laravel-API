<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Combine all validation rules into a single array
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'role' => 'nullable|in:admin,author,reader',
        ]);

        // Set default role if not provided
        $role = $validatedData['role'] ?? 'reader';

        // Handle image upload after successful validation
        $imagePath = null;
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $imagePath = $request->file('profile_picture')->store('profiles', 'public');
        }

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'profile_picture' => $imagePath,
            'role' => $role,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'data' => $user,
        ], 201);

    }


    public function login(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt to authenticate the user
        if (Auth::attempt($validatedData)) {
            $user = Auth::user(); // Get the authenticated user

            // Check if the user is not null
            if ($user) {
                $token = $user->createToken('blogLogin')->plainTextToken;

                return response()->json([
                    'status' => 'success',
                    'message' => 'User successfully logged in',
                    'data' => [
                        'token' => $token,
                        'name' => $user->name,
                        'email' => $user->email
                    ]
                ]);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid credentials',
        ], 401);
    }


    public function profile()
    {
        $user = Auth::user();

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ], 200);
    }

    public function logout()
    {
        if (Auth::check()) {
            Auth::user()->tokens()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'User successfully logged out',
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'User not authenticated',
        ], 401);
    }


}



