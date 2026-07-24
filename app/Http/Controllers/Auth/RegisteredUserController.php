<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * The very first account (empty users table) bootstraps the system: it
     * becomes an active super admin and is logged in immediately. Every
     * account after that is created as `pending` and must be approved by
     * the super admin before it can sign in.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $normalizedPhone = PhoneNumber::normalize($request->phone);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['phone' => $e->getMessage()]);
        }

        $isBootstrap = User::count() === 0;

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $normalizedPhone,
            'password' => Hash::make($request->password),
        ]);
        $user->status = $isBootstrap ? User::STATUS_ACTIVE : User::STATUS_PENDING;
        $user->is_super_admin = $isBootstrap;
        $user->approved_at = $isBootstrap ? now() : null;
        $user->save();

        if ($isBootstrap) {
            AuditLogger::log('user.registered', $user, "Pendaftaran pertama: {$user->name} ({$user->email}) otomatis menjadi admin utama.");

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        }

        AuditLogger::log('user.registered', $user, "Pendaftaran baru menunggu persetujuan: {$user->name} ({$user->email}).");

        return redirect()->route('register.pending')
            ->with('registered_email', $user->email);
    }

    /**
     * Show the "waiting for approval" information page after registering.
     */
    public function pending(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('registered_email')) {
            return redirect()->route('login');
        }

        // Keep the flash data alive so refreshing the page still works.
        $request->session()->keep(['registered_email']);

        return view('auth.register-pending', [
            'email' => $request->session()->get('registered_email'),
        ]);
    }
}
