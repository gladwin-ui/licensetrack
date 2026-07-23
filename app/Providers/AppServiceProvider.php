<?php

namespace App\Providers;

use App\Services\WhatsApp\FonnteGateway;
use App\Services\WhatsApp\LogGateway;
use App\Services\WhatsApp\MetaCloudGateway;
use App\Services\WhatsApp\WhatsAppGateway;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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
