<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedMail;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Only active accounts may reset their password. Anything else gets
        // the generic "invalid token" response.
        $account = User::where('email', $request->email)->first();
        if ($account && ! $account->isActive()) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Force logout on every device that still holds a session.
                DB::table('sessions')->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));

                AuditLogger::log('user.password_reset', $user, "Kata sandi di-reset mandiri lewat tautan email oleh {$user->name} ({$user->email}).");

                try {
                    Mail::to($user->email)->send(new PasswordChangedMail($user));
                } catch (\Exception $e) {
                    // Non-blocking: the reset itself already succeeded.
                }
            }
        );

        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', 'Kata sandi Anda berhasil diperbarui. Silakan masuk kembali.')
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
    }
}
