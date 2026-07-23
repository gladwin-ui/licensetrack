<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\OtpService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;

class InvitationController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Helper to retrieve and validate invitation.
     */
    protected function getInvitation(string $token)
    {
        $invitation = UserInvitation::where('token', $token)->first();

        if (!$invitation) {
            abort(404, 'Tautan undangan tidak ditemukan.');
        }

        if ($invitation->isAccepted()) {
            return [
                'valid' => false,
                'error' => 'Undangan ini sudah pernah digunakan untuk mendaftar.',
            ];
        }

        if ($invitation->isExpired()) {
            return [
                'valid' => false,
                'error' => 'Masa berlaku undangan ini sudah berakhir (lewat dari 48 jam). Silakan hubungi admin pengundang untuk mengirim ulang.',
            ];
        }

        return [
            'valid' => true,
            'invitation' => $invitation,
        ];
    }

    /**
     * Show the invitation accept flow page.
     */
    public function show(string $token)
    {
        $check = $this->getInvitation($token);
        if (!$check['valid']) {
            return view('auth.invitation-error', ['message' => $check['error']]);
        }

        $invitation = $check['invitation'];
        
        // Determine active step based on session states
        $step = 'select_channel';
        if (Session::get("invitation_{$token}_otp_verified")) {
            $step = 'set_password';
        } elseif (Session::get("invitation_{$token}_otp_sent")) {
            $step = 'enter_otp';
        }

        $waAvailable = $this->otpService->isWhatsAppGatewayAvailable();
        $channel = Session::get("invitation_{$token}_otp_channel");

        return view('auth.invitation-accept', compact('invitation', 'token', 'step', 'waAvailable', 'channel'));
    }

    /**
     * Step 1: Request OTP via selected channel.
     */
    public function requestOtp(Request $request, string $token)
    {
        $check = $this->getInvitation($token);
        if (!$check['valid']) {
            return redirect()->back()->with('error', $check['error']);
        }

        $invitation = $check['invitation'];

        $request->validate([
            'channel' => ['required', 'in:email,whatsapp'],
        ]);

        $channel = $request->channel;
        $identifier = $channel === 'email' ? $invitation->email : $invitation->phone;

        $result = $this->otpService->generateAndSend($identifier, 'invitation', $channel);

        if (!$result['success']) {
            return redirect()->back()->withInput()->with('error', $result['error']);
        }

        Session::put("invitation_{$token}_otp_sent", true);
        Session::put("invitation_{$token}_otp_channel", $channel);
        Session::put("invitation_{$token}_otp_target", $identifier);

        return redirect()->route('invitation.show', $token)
            ->with('status', 'Kode OTP berhasil dikirim ke ' . ($channel === 'email' ? 'Email' : 'WhatsApp') . ' Anda.');
    }

    /**
     * Step 2: Verify the OTP code.
     */
    public function verifyOtp(Request $request, string $token)
    {
        $check = $this->getInvitation($token);
        if (!$check['valid']) {
            return redirect()->back()->with('error', $check['error']);
        }

        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $identifier = Session::get("invitation_{$token}_otp_target");
        if (!$identifier) {
            return redirect()->route('invitation.show', $token)->with('error', 'Sesi verifikasi kadaluarsa. Silakan pilih kanal ulang.');
        }

        $result = $this->otpService->verify($identifier, 'invitation', $request->otp);

        if (!$result['valid']) {
            return redirect()->back()->with('error', $result['error']);
        }

        Session::put("invitation_{$token}_otp_verified", true);

        return redirect()->route('invitation.show', $token)
            ->with('status', 'OTP berhasil diverifikasi. Silakan tentukan kata sandi baru Anda.');
    }

    /**
     * Step 3: Set password and create User.
     */
    public function savePassword(Request $request, string $token)
    {
        $check = $this->getInvitation($token);
        if (!$check['valid']) {
            return redirect()->back()->with('error', $check['error']);
        }

        if (!Session::get("invitation_{$token}_otp_verified")) {
            return redirect()->route('invitation.show', $token)->with('error', 'Harap verifikasi OTP terlebih dahulu.');
        }

        $invitation = $check['invitation'];

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Create the user
        $channel = Session::get("invitation_{$token}_otp_channel");
        
        $user = User::create([
            'name'              => $invitation->name,
            'email'             => $invitation->email,
            'phone'             => $invitation->phone,
            'password'          => Hash::make($request->password),
            'is_active'         => true,
            'email_verified_at' => $channel === 'email' ? now() : null,
            'phone_verified_at' => $channel === 'whatsapp' ? now() : null,
        ]);

        // Mark invitation as accepted
        $invitation->update([
            'accepted_at' => now(),
        ]);

        // Clear session states
        Session::forget([
            "invitation_{$token}_otp_sent",
            "invitation_{$token}_otp_channel",
            "invitation_{$token}_otp_target",
            "invitation_{$token}_otp_verified",
        ]);

        AuditLogger::log('user.invitation_accepted', $user, "Undangan diterima. Admin {$user->name} ({$user->email}) terdaftar.");

        return redirect()->route('login')
            ->with('status', 'Akun administrator Anda berhasil dibuat! Silakan masuk.');
    }

    /**
     * Reset/Cancel the OTP flow to select channel again.
     */
    public function cancelOtp(string $token)
    {
        Session::forget([
            "invitation_{$token}_otp_sent",
            "invitation_{$token}_otp_channel",
            "invitation_{$token}_otp_target",
            "invitation_{$token}_otp_verified",
        ]);

        return redirect()->route('invitation.show', $token);
    }
}
