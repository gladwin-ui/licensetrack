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

        // 3. Ambil daftar slot yang sudah lewat
        $slots = config('reminder.urgent_alert.times', ['09:00', '15:00']);
        $passedSlots = [];
        foreach ($slots as $slot) {
            if ($currentTime >= $slot) {
                $passedSlots[] = $slot;
            }
        }

        if (empty($passedSlots)) {
            $this->info("Current time ({$currentTime}) has not reached any configured urgent alert slots.");
            return;
        }

        $this->info("Configured slots reached: " . implode(', ', $passedSlots));

        // 4. Ambil lisensi dengan end_date = hari ini, status active/renewed
        $licenses = License::with(['contacts', 'messageTemplate'])
            ->whereDate('end_date', $today)
            ->whereIn('status', ['active', 'renewed'])
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
            foreach ($passedSlots as $slot) {
                // Find or create UrgentAlertLog
                $log = UrgentAlertLog::firstOrCreate([
                    'license_id'  => $license->id,
                    'alert_date'  => $today->toDateString(),
                    'slot'        => $slot,
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
                    $this->warn("  ✗ License [{$license->name}] has no contacts for slot [{$slot}]. Status set to failed.");
                    $failedCount++;
                    continue;
                }

                // Kirim ke semua PIC
                $anyPicSuccess = false;

                foreach ($license->contacts as $contact) {
                    try {
                        // Tambahkan penanda slot pagi/sore ke isi pesan agar tidak identik persis
                        $slotText = ($slot === '09:00') ? 'Pagi' : 'Sore';
                        $adHocMarker = "\n*(Pengingat {$slotText} - Hari Terakhir)*";

                        if ($gateway instanceof FonnteGateway) {
                            $message = $builder->buildText($contact, $license, 0) . $adHocMarker;
                            $result  = $gateway->send($contact->phone, '', [], $message);
                            $body    = $message;
                        } else {
                            $messageData = $builder->build($license, $contact, 0);
                            $messageData['body'] .= $adHocMarker;
                            $result      = $gateway->send(
                                $contact->phone,
                                $messageData['template'],
                                $messageData['parameters']
                            );
                            $body = $messageData['body'];
                        }

                        // Record to whatsapp_messages (no reminder_log_id — ad-hoc urgent)
                        WhatsappMessage::create([
                            'reminder_log_id'    => null,
                            'license_contact_id' => $contact->id,
                            'phone'              => $contact->phone,
                            'body'               => $body,
                            'status'             => $result['success'] ? 'sent' : 'failed',
                            'wamid'              => $result['wamid'],
                            'error_message'      => $result['error'],
                            'sent_at'            => $result['success'] ? Carbon::now('Asia/Jakarta') : null,
                        ]);

                        if ($result['success']) {
                            $anyPicSuccess = true;
                            $this->info("  ✓ Urgent alert sent to [{$contact->name}] for license [{$license->name}] (Slot {$slot}).");
                        } else {
                            $this->error("  ✗ Failed to send to [{$contact->name}] (Slot {$slot}): {$result['error']}");
                        }

                    } catch (\Exception $e) {
                        Log::channel('whatsapp')->error('DispatchUrgentReminders Exception', [
                            'license' => $license->name,
                            'contact' => $contact->name,
                            'slot'    => $slot,
                            'error'   => $e->getMessage(),
                        ]);
                        $this->error("  ✗ Exception for [{$contact->name}] (Slot {$slot}): {$e->getMessage()}");
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
