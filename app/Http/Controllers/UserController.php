<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationDecisionMail;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * "Kelola Admin" page: pending registrations + administrator list.
     */
    public function index(Request $request): View
    {
        $pendingUsers = User::where('status', User::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        $query = User::where('status', '!=', User::STATUS_PENDING);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('users.index', compact('users', 'pendingUsers'));
    }

    /**
     * Approve a pending registration.
     */
    public function approve(User $user): RedirectResponse
    {
        if ($user->status !== User::STATUS_PENDING) {
            return redirect()->route('users.index')->with('error', 'Akun ini tidak sedang menunggu persetujuan.');
        }

        $user->status = User::STATUS_ACTIVE;
        $user->approved_by = Auth::id();
        $user->approved_at = now();
        $user->save();

        AuditLogger::log('user.approved', $user, "Pendaftaran {$user->name} ({$user->email}) disetujui.");

        $this->sendDecisionMail($user, approved: true);

        return redirect()->route('users.index')->with('success', "Pendaftaran {$user->name} disetujui. Akun kini dapat digunakan untuk masuk.");
    }

    /**
     * Reject a pending registration.
     */
    public function reject(User $user): RedirectResponse
    {
        if ($user->status !== User::STATUS_PENDING) {
            return redirect()->route('users.index')->with('error', 'Akun ini tidak sedang menunggu persetujuan.');
        }

        $user->status = User::STATUS_REJECTED;
        $user->save();

        AuditLogger::log('user.rejected', $user, "Pendaftaran {$user->name} ({$user->email}) ditolak.");

        $this->sendDecisionMail($user, approved: false);

        return redirect()->route('users.index')->with('success', "Pendaftaran {$user->name} ditolak.");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        if ($user->status === User::STATUS_ACTIVE) {
            if ($user->is_super_admin && $this->isLastActiveSuperAdmin($user)) {
                return redirect()->route('users.index')->with('error', 'Tidak dapat menonaktifkan. Harus ada minimal satu admin utama aktif.');
            }

            $user->status = User::STATUS_INACTIVE;
            $user->save();
            AuditLogger::log('user.deactivated', $user, "Admin {$user->name} ({$user->email}) dinonaktifkan.");

            return redirect()->route('users.index')->with('success', 'Admin berhasil dinonaktifkan.');
        }

        if (! in_array($user->status, [User::STATUS_INACTIVE, User::STATUS_REJECTED], true)) {
            return redirect()->route('users.index')->with('error', 'Status akun ini tidak dapat diubah dari sini.');
        }

        $user->status = User::STATUS_ACTIVE;
        $user->save();
        AuditLogger::log('user.activated', $user, "Admin {$user->name} ({$user->email}) diaktifkan.");

        return redirect()->route('users.index')->with('success', 'Admin berhasil diaktifkan.');
    }

    /**
     * Promote an administrator to super admin.
     */
    public function makeSuperAdmin(User $user): RedirectResponse
    {
        if ($user->is_super_admin) {
            return redirect()->route('users.index')->with('error', 'Admin ini sudah menjadi admin utama.');
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            return redirect()->route('users.index')->with('error', 'Hanya admin berstatus aktif yang dapat dijadikan admin utama.');
        }

        $user->is_super_admin = true;
        $user->save();

        AuditLogger::log('user.promoted_super_admin', $user, "Admin {$user->name} ({$user->email}) diangkat menjadi admin utama.");

        return redirect()->route('users.index')->with('success', "{$user->name} kini menjadi admin utama.");
    }

    /**
     * Send a standard password reset link to an administrator's email.
     * The super admin never sets someone else's password directly.
     */
    public function sendResetLink(User $user): RedirectResponse
    {
        if ($user->status !== User::STATUS_ACTIVE) {
            return redirect()->route('users.index')->with('error', 'Link reset hanya dapat dikirim ke akun berstatus aktif.');
        }

        Password::sendResetLink(['email' => $user->email]);

        AuditLogger::log('user.password_reset_link_sent', $user, "Link reset password dikirim ke {$user->name} ({$user->email}).");

        return redirect()->route('users.index')->with('success', "Link reset password telah dikirim ke {$user->email}.");
    }

    private function isLastActiveSuperAdmin(User $user): bool
    {
        return User::where('is_super_admin', true)
            ->where('status', User::STATUS_ACTIVE)
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }

    private function sendDecisionMail(User $user, bool $approved): void
    {
        try {
            Mail::to($user->email)->send(new RegistrationDecisionMail($user, $approved));
        } catch (\Exception $e) {
            // Non-blocking: the decision itself is already saved.
        }
    }
}
