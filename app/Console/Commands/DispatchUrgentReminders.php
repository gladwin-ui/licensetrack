<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\WhatsappMessage;
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
    protected $description = 'Send urgent WhatsApp alerts every 15 minutes for licenses expiring today.';

    /**
     * Execute the console command.
     */
    public function handle(ReminderMessageBuilder $builder, WhatsAppGateway $gateway): void
    {
        $today = Carbon::today('Asia/Jakarta');

        // Find active/renewed licenses expiring exactly today
        $licenses = License::with('contacts')
            ->whereDate('end_date', $today)
            ->whereIn('status', ['active', 'renewed'])
            ->get();

        if ($licenses->isEmpty()) {
            $this->info('No licenses expiring today. No urgent reminders sent.');
            return;
        }

        $sentCount   = 0;
        $failedCount = 0;

        foreach ($licenses as $license) {
            if ($license->contacts->isEmpty()) {
                $this->warn("License [{$license->name}] has no contacts. Skipping.");
                continue;
            }

            foreach ($license->contacts as $contact) {
                try {
                    if ($gateway instanceof FonnteGateway) {
                        // Fonnte: send free-text with milestone = 0 (expiry day)
                        $message = $builder->buildText($contact, $license, 0);
                        $result  = $gateway->send($contact->phone, '', [], $message);
                        $body    = $message;
                    } else {
                        // Meta / Log: use template with params
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
                        $this->info("  ✓ Urgent alert sent to [{$contact->name}] for license [{$license->name}].");
                        $sentCount++;
                    } else {
                        $this->error("  ✗ Failed to send to [{$contact->name}]: {$result['error']}");
                        $failedCount++;
                    }

                } catch (\Exception $e) {
                    Log::channel('whatsapp')->error('DispatchUrgentReminders Exception', [
                        'license' => $license->name,
                        'contact' => $contact->name,
                        'error'   => $e->getMessage(),
                    ]);
                    $this->error("  ✗ Exception for [{$contact->name}]: {$e->getMessage()}");
                    $failedCount++;
                }
            }
        }

        $this->info("Urgent dispatch complete. Sent: {$sentCount} | Failed: {$failedCount}");
    }
}
