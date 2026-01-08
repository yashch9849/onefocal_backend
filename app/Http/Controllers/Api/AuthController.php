<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        if (!Auth::attempt($credentials)) {
            return $this->error(
                'Invalid email or password. Please check your credentials and try again.',
                'INVALID_CREDENTIALS',
                401
            );
        }

        $user = $request->user();
        $user->load(['role', 'vendor']);

        // Admin users can always log in (exception to approval requirement)
        $isAdmin = $user->role && $user->role->name === 'admin';

        if (!$isAdmin) {
            // Check if user account is approved
            if ($user->approval_status === 'pending') {
                Auth::logout();
                return $this->error(
                    'Your account is pending approval. Please wait for administrator approval before logging in.',
                    'ACCOUNT_PENDING',
                    403
                );
            }

            if ($user->approval_status === 'rejected') {
                Auth::logout();
                $message = 'Your account has been rejected.';
                if ($user->rejection_reason) {
                    $message .= ' Reason: ' . $user->rejection_reason;
                }
                return $this->error(
                    $message,
                    'ACCOUNT_REJECTED',
                    403
                );
            }

            if ($user->approval_status !== 'approved') {
                Auth::logout();
                return $this->error(
                    'Your account is not approved. Please contact administrator.',
                    'ACCOUNT_NOT_APPROVED',
                    403
                );
            }

            // For vendor users, also check vendor status
            if ($user->role && $user->role->name === 'vendor') {
                if (!$user->vendor || $user->vendor->status !== 'approved') {
                    Auth::logout();
                    return $this->error(
                        'Your vendor account is not approved. Please wait for administrator approval.',
                        'VENDOR_NOT_APPROVED',
                        403
                    );
                }
            }
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $user,
        ], 'Login successful');
    }

    /**
     * Register a new user
     * 
     * For vendors: Creates vendor record with status=pending, then creates user
     * For customers: Creates user with status=pending
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['customer', 'vendor'])],
            // For vendors, we need vendor name to create vendor record
            'vendor_name' => ['nullable', 'required_if:role,vendor', 'string', 'max:255'],
        ], [
            'name.required' => 'Name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Role is required.',
            'role.in' => 'Role must be either "customer" or "vendor".',
            'vendor_name.required_if' => 'Vendor name is required when registering as a vendor.',
        ]);

        // Get the role
        $role = Role::where('name', $validated['role'])->first();
        
        if (!$role) {
            return $this->error(
                'Invalid role specified.',
                'INVALID_ROLE',
                400
            );
        }

        DB::beginTransaction();

        try {
            // Create user with pending approval status
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $role->id,
                'approval_status' => 'pending',
            ];

            $user = User::create($userData);

            // If role is vendor, create vendor record with user_id (1:1 relationship)
            if ($validated['role'] === 'vendor') {
                // Generate unique slug from vendor name
                $slug = Str::slug($validated['vendor_name']);
                $originalSlug = $slug;
                $counter = 1;
                
                // Ensure slug uniqueness
                while (Vendor::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                // Create vendor with pending status, linked to user
                Vendor::create([
                    'user_id' => $user->id,
                    'name' => $validated['vendor_name'],
                    'slug' => $slug,
                    'status' => 'pending',
                ]);
            }

            $user->load(['role', 'vendor']);

            DB::commit();

            return $this->success([
                'user' => $user,
                'message' => 'Registration successful. Your account is pending approval. You will be able to login once an administrator approves your account.',
            ], 'Registration successful. Your account is pending approval.', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->error(
                'Registration failed. Please try again.',
                'REGISTRATION_FAILED',
                500
            );
        }
    }
}
