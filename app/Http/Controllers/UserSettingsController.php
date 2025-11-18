<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserSettingsController extends Controller
{
    /**
     * Display user management page
     */
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->get();
        return view('user-settings', compact('users'));
    }

    /**
     * Store a new user
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => ['required', Rule::in(['admin', 'pengurus', 'lending', 'funding'])],
        ]);

        // Create user without password since authentication uses PIN
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        return back()->with('success', 'User berhasil ditambahkan. User dapat login menggunakan PIN yang dikirim ke email.');
    }

    /**
     * Update user role
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', Rule::in(['admin', 'pengurus', 'lending', 'funding'])],
        ]);

        $user->update([
            'role' => $request->role,
        ]);

        return back()->with('success', 'Role user berhasil diperbarui.');
    }

    /**
     * Delete user
     */
    public function destroy(User $user)
    {
        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus akun sendiri.']);
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }
}
