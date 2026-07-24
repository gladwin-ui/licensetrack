<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\License;
use App\Support\PhoneNumber;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ResetUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset seluruh akun pengguna (users) dan buat administrator pertama yang baru';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('=== PERINGATAN KESELAMATAN ===');
        $userCount = User::count();
        $this->error("Tindakan ini akan menghapus SELURUH {$userCount} akun pengguna di sistem!");
        $this->warn("Riwayat lisensi dan audit log TETAP utuh (referensi user diubah ke NULL).");

        $confirm = $this->ask('Ketik "HAPUS" untuk mengonfirmasi tindakan ini');

        if ($confirm !== 'HAPUS') {
            $this->info('Tindakan dibatalkan.');
            return 0;
        }

        $this->info('Memulai proses reset...');

        try {
            DB::transaction(function () {
                // 1. Null-kan created_by pada tabel licenses
                License::query()->update(['created_by' => null]);

                // 2. Null-kan user_id pada tabel audit_logs
                AuditLog::query()->update(['user_id' => null]);

                // 3. Hapus data users
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                User::query()->delete();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            });

            $this->info('Semua data pengguna lama berhasil dihapus.');

            // 5. Buat administrator pertama yang baru secara interaktif
            $this->info("\n=== Buat Administrator Pertama Yang Baru ===");
            
            $name = $this->ask('Masukkan nama administrator');
            while (empty($name)) {
                $this->error('Nama tidak boleh kosong.');
                $name = $this->ask('Masukkan nama administrator');
            }

            // Email
            $email = $this->ask('Masukkan alamat email');
            $validator = Validator::make(['email' => $email], [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            ]);
            while ($validator->fails()) {
                $this->error($validator->errors()->first('email'));
                $email = $this->ask('Masukkan alamat email');
                $validator = Validator::make(['email' => $email], [
                    'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                ]);
            }

            // Phone
            $phone = $this->ask('Masukkan nomor WhatsApp (contoh: 0812xxxxxxxx atau 628xxxxxxxx)');
            $normalizedPhone = null;
            while (empty($normalizedPhone)) {
                try {
                    $normalizedPhone = PhoneNumber::normalize($phone);
                } catch (\InvalidArgumentException $e) {
                    $this->error($e->getMessage());
                    $phone = $this->ask('Masukkan nomor WhatsApp');
                }
            }

            // Password
            $password = $this->secret('Masukkan kata sandi (minimal 8 karakter)');
            $passwordConfirm = $this->secret('Konfirmasi kata sandi');
            
            while ($password !== $passwordConfirm || strlen($password) < 8) {
                if (strlen($password) < 8) {
                    $this->error('Kata sandi minimal harus 8 karakter.');
                } else {
                    $this->error('Konfirmasi kata sandi tidak cocok.');
                }
                $password = $this->secret('Masukkan kata sandi (minimal 8 karakter)');
                $passwordConfirm = $this->secret('Konfirmasi kata sandi');
            }

            // Create user
            $user = new User([
                'name'              => $name,
                'email'             => $email,
                'phone'             => $normalizedPhone,
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
            ]);
            $user->status = User::STATUS_ACTIVE;
            $user->is_super_admin = true;
            $user->approved_at = now();
            $user->save();

            // 6. Catat ke audit log
            AuditLogger::log('system.users_reset', null, "Sistem di-reset. Admin utama pertama [{$user->name}] ({$user->email}) dibuat.");

            $this->info("\nAdmin utama pertama [{$user->name}] berhasil dibuat.");
            $this->info("Silakan login menggunakan email [{$user->email}].");

        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan saat mereset database: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
