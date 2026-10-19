<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Modo demo (sin correo real): se genera el token y el enlace se muestra
        // en pantalla en lugar de enviarse. Ver config/auth.php.
        if (config('auth.demo_reset_link')) {
            $user = User::where('email', $request->input('email'))->first();

            if (! $user) {
                return $this->unknownEmail($request);
            }

            $token = Password::broker()->createToken($user);

            return back()
                ->with('status', 'Modo demo: no se envían correos. Usa este enlace para elegir una contraseña nueva.')
                ->with('reset_link', route('password.reset', ['token' => $token, 'email' => $user->email]));
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Te hemos enviado un enlace para cambiar la contraseña.');
        }

        return $this->unknownEmail($request);
    }

    private function unknownEmail(Request $request): RedirectResponse
    {
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'No hay ninguna cuenta con ese correo.']);
    }
}
