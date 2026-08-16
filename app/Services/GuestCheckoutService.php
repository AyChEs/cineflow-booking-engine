<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GuestCheckoutService
{
    /**
     * Get or create a guest user for checkout
     * If authenticated, returns the current user
     * If not, creates/returns a temporary guest user session
     * 
     * @param string|null $email Customer email (if available)
     * @param string|null $telefono Customer phone (if available)
     * @return User The authenticated user or a guest user token
     */
    public static function getOrCreateGuestUser(?string $email = null, ?string $telefono = null): User
    {
        // If user is already authenticated, use their account
        if (Auth::check()) {
            return Auth::user();
        }

        // Create a temporary guest user
        // Guest users have a special email format: guest-{timestamp-uuid}@cineflow.local
        // This allows multiple simultaneous guest checkouts
        $guestEmail = 'guest-' . Str::uuid() . '@cineflow.local';
        
        $guestUser = User::firstOrCreate(
            ['email' => $guestEmail],
            [
                'name'       => 'Guest',
                'apellidos'  => Str::uuid(),
                'email'      => $guestEmail,
                'password'   => Str::random(32), // Random password - guest cannot login
                'rol'        => 'guest',
                'telefono'   => $telefono ?? null,
            ]
        );

        // Log them in temporarily for this session
        Auth::login($guestUser, remember: false);

        return $guestUser;
    }

    /**
     * Check if a user is a guest user
     */
    public static function isGuest(User $user): bool
    {
        return $user->rol === 'guest' || Str::contains($user->email, '@cineflow.local');
    }

    /**
     * Guarda el email real que el invitado indica durante el checkout,
     * conservando el email de sistema para la autenticación.
     */
    public static function updateGuestEmail(User $guestUser, string $realEmail): void
    {
        if (self::isGuest($guestUser)) {
            $guestUser->update(['telefono' => $realEmail]);
        }
    }
}
