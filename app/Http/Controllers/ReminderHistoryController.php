<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppReminder;
use App\Models\License;
use App\Models\WhatsappMessage;
use App\Services\ReminderMessageBuilder;
use App\Services\WhatsApp\FonnteGateway;
use App\Services\WhatsApp\WhatsAppGateway;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use App\Services\AuditLogger;

class ReminderHistoryController extends Controller
{
    /**
     * Display the history of WhatsApp messages.
     */
    public function index(Request $request): View
    {
        $query = WhatsappMessage::with(['reminderLog.license', 'contact.license']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('reminderLog.license', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                })->orWhereHas('contact', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                });
            });
        }

        $summary = [
            'sent' => WhatsappMessage::where('status', 'sent')->count(),
            'failed' => WhatsappMessage::where('status', 'failed')->count(),
            'pending' => WhatsappMessage::where('status', 'pending')->count(),
        ];

        $messages = $query->latest()->paginate(25)->withQueryString();

        return view('reminders.index', compact('messages', 'summary'));
    }

    /**
     * Retry a single failed message.
     */
    public function retry(WhatsappMessage $message, WhatsAppGateway $gateway): RedirectResponse
    {
        if ($message->status !== 'failed') {
            return back()->with('error', 'Hanya pesan yang gagal yang dapat dicoba ulang.');
        }

        $contact = $message->contact;
        
        if (!$contact) {
            return back()->with('error', 'Kontak PIC sudah dihapus.');
        }
        
        $license = $message->reminderLog ? $message->reminderLog->license : $contact->license;
        $milestone = $message->reminderLog ? $message->reminderLog->milestone : 0;

        $builder = app(ReminderMessageBuilder::class);

        try {
            if ($gateway instanceof FonnteGateway) {
                $bodyText = $builder->buildText($contact, $license, $milestone);
                $result = $gateway->send($contact->phone, '', [], $bodyText);
            } else {
                $messageData = $builder->build($license, $contact, $milestone);
                $result = $gateway->send(
                    $contact->phone,
                    $messageData['template'],
                    $messageData['parameters']
                );
            }

            if ($result['success']) {
                $message->update([
                    'status'        => 'sent',
                    'wamid'         => $result['wamid'],
                    'sent_at'       => Carbon::now('Asia/Jakarta'),
                    'error_message' => null,
                ]);

                AuditLogger::log('reminder.sent_manual', $message, "Mengirim ulang pengingat WhatsApp gagal ke nomor {$contact->phone}.");

                return back()->with('success', 'Pesan berhasil dikirim ulang.');
            }

            $message->update([
                'error_message' => $result['error'],
            ]);

            return back()->with('error', 'Gagal mengirim ulang: ' . $result['error']);
        } catch (\Exception $e) {
            $message->update([
                'error_message' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal mengirim ulang: ' . $e->getMessage());
        }
    }

    /**
     * Manually trigger reminder alert for licenses expiring H-7 to H-1 (and H-0).
     */
    public function dispatchNow(WhatsAppGateway $gateway, ReminderMessageBuilder $builder): RedirectResponse
    {
        Artisan::call('reminders:dispatch');

        $today = Carbon::today('Asia/Jakarta');

        $licenses = License::with('contacts')
            ->whereIn('status', ['active', 'renewed'])
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $today->copy()->addDays(7))
            ->get();

        if ($licenses->isEmpty()) {
            return back()->with('success', 'Antrean diproses. Tidak ada lisensi aktif dalam rentang H-7 s/d H-1 untuk pengiriman manual.');
        }

        $sent = 0;
        $failed = 0;

        foreach ($licenses as $license) {
            if ($license->contacts->isEmpty()) {
                continue;
            }

            $days = $today->diffInDays($license->end_date, false);

            foreach ($license->contacts as $contact) {
                try {
                    if ($gateway instanceof FonnteGateway) {
                        $bodyText = $builder->buildText($contact, $license, $days);
                        $result = $gateway->send($contact->phone, '', [], $bodyText);
                    } else {
                        $messageData = $builder->build($license, $contact, $days >= 0 ? 0 : -1);
                        $result = $gateway->send(
                            $contact->phone,
                            $messageData['template'],
                            $messageData['parameters']
                        );
                        $bodyText = $messageData['body'];
                    }

                    WhatsappMessage::create([
                        'reminder_log_id'    => null,
                        'license_contact_id' => $contact->id,
                        'phone'              => $contact->phone,
                        'body'               => $bodyText,
                        'status'             => $result['success'] ? 'sent' : 'failed',
                        'wamid'              => $result['wamid'],
                        'error_message'      => $result['error'],
                        'sent_at'            => $result['success'] ? Carbon::now('Asia/Jakarta') : null,
                    ]);

                    if ($result['success']) {
                        $sent++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                }
            }
        }

        AuditLogger::log('reminder.sent_manual', null, "Proses antrean reminder manual dijalankan. Berhasil: {$sent}, Gagal: {$failed}.");

        $msg = "Pengiriman manual alert H-7 s/d H-1 selesai. Berhasil: {$sent} PIC, Gagal: {$failed}.";
        if ($failed > 0) {
            return back()->with('error', $msg);
        }

        return back()->with('success', $msg);
    }

    /**
     * Send an ad-hoc reminder now for a specific license.
     */
    public function sendNow(License $license, WhatsAppGateway $gateway, ReminderMessageBuilder $builder): RedirectResponse
    {
        $license->load('contacts');
        
        if ($license->contacts->isEmpty()) {
            return back()->with('error', 'Lisensi tidak memiliki kontak PIC.');
        }

        $sent = 0;
        $failed = 0;

        foreach ($license->contacts as $contact) {
            $days = Carbon::today('Asia/Jakarta')->diffInDays($license->end_date, false);
            $milestoneForTemplate = $days >= 0 ? 0 : -1;

            if ($gateway instanceof FonnteGateway) {
                $bodyText = $builder->buildText($contact, $license, $days);
                $result = $gateway->send($contact->phone, '', [], $bodyText);
            } else {
                $messageData = $builder->build($license, $contact, $milestoneForTemplate);
                $result = $gateway->send(
                    $contact->phone,
                    $messageData['template'],
                    $messageData['parameters']
                );
                $bodyText = $messageData['body'];
            }

            WhatsappMessage::create([
                'reminder_log_id'    => null, // ad-hoc
                'license_contact_id' => $contact->id,
                'phone'              => $contact->phone,
                'body'               => $bodyText,
                'status'             => $result['success'] ? 'sent' : 'failed',
                'wamid'              => $result['wamid'],
                'error_message'      => $result['error'],
                'sent_at'            => $result['success'] ? Carbon::now('Asia/Jakarta') : null,
            ]);

            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
            }
        }

        AuditLogger::log('reminder.sent_manual', $license, "Mengirim pengingat ad-hoc manual untuk lisensi {$license->name}. Berhasil: {$sent}, Gagal: {$failed}.");

        $msg = "Reminder ad-hoc selesai. Berhasil: {$sent}, Gagal: {$failed}.";
        if ($failed > 0) {
            return back()->with('error', $msg);
        }
        return back()->with('success', $msg);
    }
}
