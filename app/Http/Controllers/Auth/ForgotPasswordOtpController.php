<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\AuditLogger;
use App\Mail\PasswordChangedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class ForgotPasswordOtpController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Show email request form.
     */
    public function showRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send OTP code for password reset.
     */
    public function sendResetOtp(Request $request)
    {
        $request->validate([
            'email'   => ['required', 'email'],
            'channel' => ['required', 'in:email,whatsapp'],
        ]);

        $email = $request->email;
        $channel = $request->channel;

        $user = User::where('email', $email)->where('is_active', true)->first();

        // Standard fake response to prevent email enumeration
        $successMsg = 'Jika alamat email tersebut terdaftar, kode OTP telah dikirimkan ke ' . ($channel === 'email' ? 'Email' : 'WhatsApp') . ' Anda.';

        if (!$user) {
            // Fake session to mimic success
            Session::put('reset_email', $email);
            Session::put('reset_otp_sent', true);
            Session::put('reset_otp_channel', $channel);
            Session::put('reset_otp_target', $channel === 'email' ? $email : 'unknown');
            return redirect()->route('password.otp.verify')->with('status', $successMsg);
        }

        $identifier = $channel === 'email' ? $user->email : $user->phone;

        if (empty($identifier)) {
            return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp Anda belum terdaftar di profil. Silakan pilih kanal Email.');
        }

        $result = $this->otpService->generateAndSend($identifier, 'password_reset', $channel);

        if (!$result['success']) {
            return redirect()->back()->withInput()->with('error', $result['error']);
        }

        Session::put('reset_email', $user->email);
        Session::put('reset_otp_sent', true);
        Session::put('reset_otp_channel', $channel);
        Session::put('reset_otp_target', $identifier);

        return redirect()->route('password.otp.verify')->with('status', $successMsg);
    }

    /**
     * Show OTP verification form.
     */
    public function showVerifyForm()
    {
        if (!Session::get('reset_otp_sent')) {
            return redirect()->route('password.request');
        }

        $email = Session::get('reset_email');
        $channel = Session::get('reset_otp_channel');
        $target = Session::get('reset_otp_target');
        
        $waAvailable = $this->otpService->isWhatsAppGatewayAvailable();

        return view('auth.verify-otp', compact('email', 'channel', 'target', 'waAvailable'));
    }

    /**
     * Verify the entered reset OTP.
     */
    public function verifyResetOtp(Request $request)
    {
        if (!Session::get('reset_otp_sent')) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $email = Session::get('reset_email');
        $target = Session::get('reset_otp_target');

        // If it was a fake flow (email did not exist)
        if ($target === 'unknown' || !$email) {
            return redirect()->back()->withErrors(['otp' => 'Kode OTP salah atau tidak cocok.']);
        }

        $result = $this->otpService->verify($target, 'password_reset', $request->otp);

        if (!$result['valid']) {
            return redirect()->back()->with('error', $result['error']);
        }

        Session::put('reset_otp_verified', true);

        return redirect()->route('password.otp.reset')
            ->with('status', 'OTP berhasil diverifikasi. Silakan tentukan kata sandi baru Anda.');
    }

    /**
     * Show reset password form.
     */
    public function showResetForm()
    {
        if (!Session::get('reset_otp_verified')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password-otp');
    }

    /**
     * Update the user password, log out all active sessions.
     */
    public function resetPassword(Request $request)
    {
        if (!Session::get('reset_otp_verified')) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $email = Session::get('reset_email');
        $user = User::where('email', $email)->where('is_active', true)->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Akun tidak ditemukan.');
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Force logout all active sessions by deleting from sessions table
        try {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        } catch (\Exception $e) {
            // Non-blocking fallback
        }

        // Send email notification to user
        try {
            Mail::to($user->email)->send(new PasswordChangedMail($user));
        } catch (\Exception $e) {
            // Non-blocking
        }

        // Audit log
        AuditLogger::log('user.password_reset', $user, "Kata sandi di-reset secara mandiri menggunakan OTP.");

        // Clear session states
        Session::forget([
            'reset_email',
            'reset_otp_sent',
            'reset_otp_channel',
            'reset_otp_target',
            'reset_otp_verified',
        ]);

        return redirect()->route('login')
            ->with('status', 'Kata sandi Anda berhasil diperbarui! Silakan masuk.');
    }

    /**
     * Reset OTP state to try again.
     */
    public function cancelReset()
    {
        Session::forget([
            'reset_email',
            'reset_otp_sent',
            'reset_otp_channel',
            'reset_otp_target',
            'reset_otp_verified',
        ]);

        return redirect()->route('password.request');
    }
}
