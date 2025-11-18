<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailPinCode extends Model
{
    protected $fillable = [
        'email',
        'pin_code',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Generate a new PIN code for email
     */
    public static function generateForEmail(string $email): self
    {
        // Delete any existing unused PIN codes for this email
        self::where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->delete();

        // Generate 6-digit PIN
        $pinCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        return self::create([
            'email' => $email,
            'pin_code' => $pinCode,
            'expires_at' => now()->addMinutes(10), // PIN expires in 10 minutes
        ]);
    }

    /**
     * Verify PIN code
     */
    public static function verifyPin(string $email, string $pinCode): bool
    {
        $pinRecord = self::where('email', $email)
            ->where('pin_code', $pinCode)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($pinRecord) {
            $pinRecord->update(['used_at' => now()]);
            return true;
        }

        return false;
    }

    /**
     * Check if PIN is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if PIN is used
     */
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    /**
     * Scope for unused PIN codes
     */
    public function scopeUnused($query)
    {
        return $query->whereNull('used_at');
    }

    /**
     * Scope for unexpired PIN codes
     */
    public function scopeUnexpired($query)
    {
        return $query->where('expires_at', '>', now());
    }
}
