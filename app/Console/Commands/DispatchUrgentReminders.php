<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\WhatsappMessage;
use App\Models\UrgentAlertLog;
use App\Services\ReminderMessageBuilder;
use App\Services\WhatsApp\FonnteGateway;
use App\Services\WhatsApp\WhatsAppGateway;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchUrgentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:dispatch-urgent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send urgent WhatsApp alerts at scheduled times of the day for licenses expiring today.';

    /**
     * Execute the console command.
     */
    public function handle(ReminderMessageBuilder $builder, WhatsAppGateway $gateway): void
    {
        // 1. Cek config
        $enabled = config('reminder.urgent_alert.enabled', true);
        if (!$enabled) {
            $this->info('Urgent alerts are disabled in config.');
            return;
        }

        // 2. Tentukan hari ini (Asia/Jakarta)
        $today = Carbon::today('Asia/Jakarta');
        $currentTime = Carbon::now('Asia/Jakarta')->format('H:i');

        // 3. Ambil daftar slot yang sudah lewat secara dinamis
        $time1 = setting('urgent_alert_time_1') ?: '09:00';
        $time2 = setting('urgent_alert_time_2') ?: '15:00';

        $slots = [
            'slot_1' => $time1,
            'slot_2' => $time2,
        ];

        $passedSlots = [];
        foreach ($slots as $position => $time) {
            if ($currentTime >= $time) {
                $passedSlots[$position] = $time;
            }
        }

        if (empty($passedSlots)) {
            $this->info("Current time ({$currentTime}) has not reached any configured urgent alert slots.");
            return;
        }

        $this->info("Configured slots reached: " . implode(', ', array_values($passedSlots)) . " (" . implode(', ', array_keys($passedSlots)) . ")");

        // 4. Ambil lisensi dengan end_date = hari ini, status active/renewed, dan lifecycle_status active
        $licenses = License::with(['contacts', 'messageTemplate'])
            ->whereDate('end_date', $today)
            ->whereIn('status', ['active', 'renewed'])
            ->where('lifecycle_status', 'active')
            ->get();

        if ($licenses->isEmpty()) {
            $this->info('No licenses expiring today. No urgent reminders to process.');
            return;
        }

        $sentCount   = 0;
        $skippedCount = 0;
        $failedCount = 0;

        // 5. Untuk setiap kombinasi (lisensi x slot yang sudah lewat)
        foreach ($licenses as $license) {
            foreach ($passedSlots as $position => $time) {
                // Find or create UrgentAlertLog berdasarkan posisi slot ('slot_1' atau 'slot_2')
                $log = UrgentAlertLog::firstOrCreate([
                    'license_id'  => $license->id,
                    'alert_date'  => $today->toDateString(),
                    'slot'        => $position,
                ], [
                    'status'      => 'pending',
                ]);

                // Jika status sudah 'sent', lewati
                if ($log->status === 'sent') {
                    $skippedCount++;
                    continue;
                }

                // Jika lisensi tidak punya PIC, tandai failed
                if ($license->contacts->isEmpty()) {
                    $log->update([
                        'status' => 'failed',
                    ]);
                    $this->warn("  ✗ License [{$license->name}] has no contacts for slot [{$position}]. Status set to failed.");
                    $failedCount++;
                    continue;
                }

                // Kirim ke semua PIC
                $anyPicSuccess = false;

                foreach ($license->contacts as $contact) {
                    try {
                         if ($gateway instanceof FonnteGateway) {
                             $message = $builder->buildText($contact, $license, 0);
                             $result  = $gateway->send($contact->phone, '', [], $message);
                             $body    = $message;
                         } else {
                             $messageData = $builder->build($license, $contact, 0);
                             $result      = $gateway->send(
                                 $contact->phone,
                                 $messageData['template'],
                                 $messageData['parameters']
                             );
                             $body = $messageData['body'];
                         }

                        // Record to whatsapp_messages (no reminder_log_id — ad-hoc urgent)
                        WhatsappMessage::create([
                            'reminder_log_id'     => null,
                            'urgent_alert_log_id' => $log->id,
                            'license_contact_id'  => $contact->id,
                            'phone'               => $contact->phone,
                            'body'                => $body,
                            'status'              => $result['success'] ? 'sent' : 'failed',
                            'wamid'               => $result['wamid'],
                            'error_message'       => $result['error'],
                            'sent_at'             => $result['success'] ? Carbon::now('Asia/Jakarta') : null,
                        ]);

                        if ($result['success']) {
                            $anyPicSuccess = true;
                            $this->info("  ✓ Urgent alert sent to [{$contact->name}] for license [{$license->name}] (Slot {$position}).");
                        } else {
                            $this->error("  ✗ Failed to send to [{$contact->name}] (Slot {$position}): {$result['error']}");
                        }

                    } catch (\Exception $e) {
                        Log::channel('whatsapp')->error('DispatchUrgentReminders Exception', [
                            'license' => $license->name,
                            'contact' => $contact->name,
                            'slot'    => $position,
                            'error'   => $e->getMessage(),
                        ]);
                        $this->error("  ✗ Exception for [{$contact->name}] (Slot {$position}): {$e->getMessage()}");
                    }
                }

                if ($anyPicSuccess) {
                    $log->update([
                        'status'  => 'sent',
                        'sent_at' => Carbon::now('Asia/Jakarta'),
                    ]);
                    $sentCount++;
                } else {
                    $log->update([
                        'status'  => 'failed',
                    ]);
                    $failedCount++;
                }
            }
        }

        $this->info("\n=== Ringkasan Dispatch Urgent ===");
        $this->info("Terkirim (Baru): {$sentCount}");
        $this->info("Dilewati (Sudah Terkirim): {$skippedCount}");
        $this->info("Gagal: {$failedCount}");
    }
}
