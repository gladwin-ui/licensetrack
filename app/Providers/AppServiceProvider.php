<?php

namespace App\Providers;

use App\Models\User;
use App\Services\WhatsApp\FonnteGateway;
use App\Services\WhatsApp\LogGateway;
use App\Services\WhatsApp\MetaCloudGateway;
use App\Services\WhatsApp\WhatsAppGateway;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;
use App\Services\AuditLogger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind WhatsAppGateway interface to the correct implementation
        // based on the setting() value.
        $this->app->bind(WhatsAppGateway::class, function () {
            $gateway = setting('wa_gateway', config('whatsapp.gateway', 'log'));

            return match ($gateway) {
                'meta'   => new MetaCloudGateway(),
                'fonnte' => new FonnteGateway(),
                'log'    => new LogGateway(),
                default  => new LogGateway(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (file_exists(base_path('logo_utama_hdp.png')) && !file_exists(public_path('logo_utama_hdp.png'))) {
            @copy(base_path('logo_utama_hdp.png'), public_path('logo_utama_hdp.png'));
        }

        // Only the super admin may manage administrator accounts.
        Gate::define('manage-admins', fn (User $user) => $user->is_super_admin);

        // Public registration form: max 3 sign-ups per hour per IP address.
        RateLimiter::for('registration', function (Request $request) {
            return Limit::perHour(3)->by($request->ip())->response(function () {
                return back()->withErrors([
                    'email' => 'Terlalu banyak pendaftaran dari jaringan Anda. Silakan coba lagi dalam satu jam.',
                ]);
            });
        });

        // Indonesian reset-password email (link expires in 60 minutes).
        ResetPassword::toMailUsing(function (User $user, string $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));

            return (new MailMessage)
                ->subject('Reset Kata Sandi — LicenseTrack')
                ->greeting("Halo, {$user->name}")
                ->line('Kami menerima permintaan reset kata sandi untuk akun Anda di LicenseTrack.')
                ->action('Reset Kata Sandi', $url)
                ->line('Tautan ini berlaku selama 60 menit. Jika Anda tidak meminta reset kata sandi, abaikan email ini.')
                ->salutation('Salam, LicenseTrack');
        });

        // Listen to Login event
        Event::listen(Login::class, function (Login $event) {
            $event->user->update([
                'last_login_at' => Carbon::now('Asia/Jakarta'),
            ]);

            AuditLogger::log(
                'login',
                $event->user,
                "Admin {$event->user->name} ({$event->user->email}) berhasil login."
            );
        });

        // Listen to Failed login event
        Event::listen(Failed::class, function (Failed $event) {
            $email = $event->credentials['email'] ?? 'unknown';
            AuditLogger::log(
                'login_failed',
                null,
                "Gagal login menggunakan email: {$email}."
            );
        });

        // Listen to Logout event
        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                AuditLogger::log(
                    'logout',
                    $event->user,
                    "Admin {$event->user->name} ({$event->user->email}) logout."
                );
            }
        });
    }
}
