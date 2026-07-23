<?php

namespace App\Jobs;

use App\Models\ReminderLog;
use App\Models\WhatsappMessage;
use App\Services\ReminderMessageBuilder;
use App\Services\WhatsApp\FonnteGateway;
use App\Services\WhatsApp\WhatsAppGateway;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ReminderLog $reminderLog
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsAppGateway $gateway, ReminderMessageBuilder $messageBuilder): void
    {
        $this->reminderLog->loadMissing(['license', 'license.contacts']);
        $license = $this->reminderLog->license;

        if (!$license || $license->contacts->isEmpty()) {
            $this->reminderLog->update(['status' => 'failed']);
            return;
        }

        $allFailed = true;

        foreach ($license->contacts as $contact) {
            if ($gateway instanceof FonnteGateway) {
                // Fonnte: kirim teks bebas
                $messageText = $messageBuilder->buildText($contact, $license, $this->reminderLog->milestone);
                $bodyText = $messageText;

                // Create pending message record
                $waMessage = WhatsappMessage::create([
                    'reminder_log_id'    => $this->reminderLog->id,
                    'license_contact_id' => $contact->id,
                    'phone'              => $contact->phone,
                    'body'               => $bodyText,
                    'status'             => 'pending',
                ]);

                // Send the message
                $result = $gateway->send($contact->phone, '', [], $messageText);
            } else {
                // Meta / Log: kirim template dengan parameter
                $messageData = $messageBuilder->build($license, $contact, $this->reminderLog->milestone);
                $bodyText = $messageData['body'];

                // Create pending message record
                $waMessage = WhatsappMessage::create([
                    'reminder_log_id'    => $this->reminderLog->id,
                    'license_contact_id' => $contact->id,
                    'phone'              => $contact->phone,
                    'body'               => $bodyText,
                    'status'             => 'pending',
                ]);

                // Send the message
                $result = $gateway->send(
                    $contact->phone,
                    $messageData['template'],
                    $messageData['parameters']
                );
            }

            if ($result['success']) {
                // Update success
                $waMessage->update([
                    'status'  => 'sent',
                    'wamid'   => $result['wamid'],
                    'sent_at' => Carbon::now('Asia/Jakarta'),
                ]);
                $allFailed = false;
            } else {
                // Failure
                $waMessage->update([
                    'status'        => 'failed',
                    'error_message' => $result['error'],
                ]);

                if ($result['permanent']) {
                    Log::error("Permanent failure sending WhatsApp to {$contact->phone}: " . $result['error']);
                    // Do not throw exception, so it doesn't retry
                } else {
                    Log::error("Temporary failure sending WhatsApp to {$contact->phone}: " . $result['error']);
                    // Throw exception to trigger job retry
                    throw new \Exception($result['error']);
                }
            }
        }

        // Determine final reminder log status
        if ($allFailed) {
            $this->reminderLog->update(['status' => 'failed']);
        } else {
            $this->reminderLog->update([
                'status'  => 'sent',
                'sent_at' => Carbon::now('Asia/Jakarta'),
            ]);
        }
    }
}
