<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^(\\+?62|0)[0-9]{8,18}$/', 'unique:users,whatsapp_number'],
            'role' => ['required', Rule::in(['admin', 'pengurus', 'lending', 'funding'])],
        ]);

        $whatsappNumber = $this->normalizeWhatsAppNumber($request->whatsapp_number);

        // Create user without password since authentication uses PIN
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp_number' => $whatsappNumber,
            'role' => $request->role,
        ]);

        return back()->with('success', 'User berhasil ditambahkan. OTP akan dikirim via WhatsApp jika nomor terdaftar, jika tidak maka via email.');
    }

    /**
     * Update user role
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', Rule::in(['admin', 'pengurus', 'lending', 'funding'])],
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^(\\+?62|0)[0-9]{8,18}$/', Rule::unique('users', 'whatsapp_number')->ignore($user->id)],
        ]);

        $whatsappNumber = $this->normalizeWhatsAppNumber($request->whatsapp_number);

        $user->update([
            'role' => $request->role,
            'whatsapp_number' => $whatsappNumber,
        ]);

        return back()->with('success', 'Data user berhasil diperbarui.');
    }

    /**
     * Delete user
     */
    public function destroy(User $user)
    {
        // Prevent deleting self
        $currentUserId = Auth::id();

        if ((string) $user->getAuthIdentifier() === (string) $currentUserId) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus akun sendiri.']);
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    private function normalizeWhatsAppNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $normalized = preg_replace('/[^0-9]/', '', $phone);

        if (!$normalized) {
            return null;
        }

        if (str_starts_with($normalized, '0')) {
            $normalized = '62' . substr($normalized, 1);
        }

        if (!str_starts_with($normalized, '62')) {
            return null;
        }

        return $normalized;
    }
}
