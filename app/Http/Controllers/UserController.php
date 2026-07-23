<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();
        
        // Fetch pending invitations (expires in future or expired but not accepted)
        $pendingInvitations = \App\Models\UserInvitation::whereNull('accepted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('users.index', compact('users', 'pendingInvitations'));
    }

    /**
     * Store a newly created invitation in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:user_invitations,email'],
            'phone' => ['required', 'string'],
        ]);

        try {
            $normalizedPhone = \App\Support\PhoneNumber::normalize($request->phone);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['phone' => $e->getMessage()]);
        }

        $invitation = \App\Models\UserInvitation::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'phone'      => $normalizedPhone,
            'token'      => \Illuminate\Support\Str::random(64),
            'invited_by' => Auth::id(),
            'expires_at' => now()->addHours(48),
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($invitation->email)->send(new \App\Mail\InvitationMail($invitation));
        } catch (\Exception $e) {
            $invitation->delete();
            return redirect()->route('users.index')->with('error', 'Gagal mengirim email undangan: ' . $e->getMessage());
        }

        // Optional: Send a short notification to WhatsApp if active
        $otpService = app(\App\Services\OtpService::class);
        if ($otpService->isWhatsAppGatewayAvailable()) {
            try {
                $waGateway = app(\App\Services\WhatsApp\WhatsAppGateway::class);
                $message = "Halo {$invitation->name}, Anda telah diundang untuk bergabung sebagai administrator di LicenseTrack. Silakan periksa email Anda ({$invitation->email}) untuk menerima undangan.";
                $waGateway->send($invitation->phone, 'invitation_notice', ['name' => $invitation->name, 'email' => $invitation->email], $message);
            } catch (\Exception $e) {
                // Non-blocking, email is the primary channel
            }
        }

        AuditLogger::log('user.invited', null, "Admin baru diundang: {$invitation->name} ({$invitation->email})");

        return redirect()->route('users.index')->with('success', "Undangan berhasil dikirim ke {$invitation->email}.");
    }

    /**
     * Resend user invitation.
     */
    public function resendInvitation(\App\Models\UserInvitation $invitation): RedirectResponse
    {
        if ($invitation->isAccepted()) {
            return redirect()->route('users.index')->with('error', 'Undangan sudah diterima oleh pengguna.');
        }

        // Update token and expiry
        $invitation->update([
            'token'      => \Illuminate\Support\Str::random(64),
            'expires_at' => now()->addHours(48),
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($invitation->email)->send(new \App\Mail\InvitationMail($invitation));
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Gagal mengirim ulang email undangan: ' . $e->getMessage());
        }

        AuditLogger::log('user.invitation_resent', null, "Undangan dikirim ulang ke: {$invitation->name} ({$invitation->email})");

        return redirect()->route('users.index')->with('success', "Undangan berhasil dikirim ulang ke {$invitation->email}.");
    }

    /**
     * Cancel/Delete user invitation.
     */
    public function cancelInvitation(\App\Models\UserInvitation $invitation): RedirectResponse
    {
        if ($invitation->isAccepted()) {
            return redirect()->route('users.index')->with('error', 'Undangan sudah diterima dan tidak dapat dibatalkan.');
        }

        $invitation->delete();

        AuditLogger::log('user.invitation_cancelled', null, "Undangan dibatalkan untuk: {$invitation->name} ({$invitation->email})");

        return redirect()->route('users.index')->with('success', 'Undangan berhasil dibatalkan.');
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $currentUser = Auth::user();

        // 1. Cannot deactivate oneself
        if ($user->id === $currentUser->id) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        // 2. Prevent deactivating the last active admin
        if ($user->is_active) {
            $activeCount = User::where('is_active', true)->count();
            if ($activeCount <= 1) {
                return redirect()->route('users.index')->with('error', 'Tidak dapat menonaktifkan. Harus ada minimal satu administrator aktif.');
            }

            $user->update(['is_active' => false]);
            AuditLogger::log('user.deactivated', $user, "Admin {$user->name} ({$user->email}) dinonaktifkan.");
            return redirect()->route('users.index')->with('success', 'Admin berhasil dinonaktifkan.');
        }

        $user->update(['is_active' => true]);
        AuditLogger::log('user.activated', $user, "Admin {$user->name} ({$user->email}) diaktifkan kembali.");
        return redirect()->route('users.index')->with('success', 'Admin berhasil diaktifkan kembali.');
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLogger::log('user.password_reset', $user, "Password untuk admin {$user->name} ({$user->email}) di-reset oleh admin.");

        return redirect()->route('users.index')->with('success', 'Password admin berhasil diubah.');
    }
}
