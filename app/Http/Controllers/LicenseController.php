<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLicenseRequest;
use App\Http\Requests\UpdateLicenseRequest;
use App\Models\License;
use App\Models\LicenseContact;
use App\Models\LicenseFile;
use App\Services\ReminderScheduler;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Services\AuditLogger;

class LicenseController extends Controller
{
    /**
     * Display a listing of licenses.
     */
    public function index(Request $request): View
    {
        $today = Carbon::today('Asia/Jakarta');

        $query = License::with('contacts')->orderBy('end_date', 'asc');

        // Filter by lifecycle_status: active (default) / stopped / all
        $lifecycleFilter = $request->input('lifecycle_status', 'active');
        if ($lifecycleFilter === 'active') {
            $query->where('lifecycle_status', 'active');
        } elseif ($lifecycleFilter === 'stopped') {
            $query->where('lifecycle_status', 'stopped');
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('vendor')) {
            $query->where('vendor', 'like', '%' . $request->vendor . '%');
        }

        if ($request->filled('health')) {
            match ($request->health) {
                'aman' => $query->where('end_date', '>', $today->copy()->addDays(90)),
                'waspada' => $query->whereBetween('end_date', [$today->copy()->addDays(31), $today->copy()->addDays(90)]),
                'kritis' => $query->whereBetween('end_date', [$today->copy()->addDay(), $today->copy()->addDays(30)]),
                'expired' => $query->where('end_date', '<=', $today),
                default => null,
            };
        }

        $licenses = $query->paginate(15)->withQueryString();

        $totalLicenses = License::where('lifecycle_status', 'active')->count();
        $criticalCount = License::where('lifecycle_status', 'active')->expiringWithin(30)->count();
        $expiredCount = License::where('lifecycle_status', 'active')->expired()->count();
        $vendors = License::where('lifecycle_status', 'active')
            ->whereNotNull('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->pluck('vendor');

        return view('licenses.index', compact('licenses', 'totalLicenses', 'criticalCount', 'expiredCount', 'vendors'));
    }

    /**
     * Show the form for creating a new license.
     */
    public function create(): View
    {
        $templates = \App\Models\MessageTemplate::orderBy('name')->get();
        return view('licenses.create', compact('templates'));
    }

    /**
     * Store a newly created license in storage.
     */
    public function store(StoreLicenseRequest $request, ReminderScheduler $scheduler): RedirectResponse
    {
        $license = DB::transaction(function () use ($request) {
            $reminderMode = $request->reminder_mode;
            $overdueMode = $request->overdue_reminder_mode;

            $messageTemplateId = $reminderMode === 'template' ? $request->message_template_id : null;
            $messageIntro = $reminderMode === 'custom' ? $request->message_intro : null;
            $messageClosing = $reminderMode === 'custom' ? $request->message_closing : null;

            $overdueTemplateId = $overdueMode === 'template' ? $request->overdue_message_template_id : null;
            $overdueIntro = $overdueMode === 'custom' ? $request->overdue_message_intro : null;
            $overdueClosing = $overdueMode === 'custom' ? $request->overdue_message_closing : null;

            // Create the license record
            $license = License::create([
                'name'                        => $request->name,
                'vendor'                      => $request->vendor,
                'description'                 => $request->description,
                'license_key'                 => $request->license_key,
                'start_date'                  => $request->start_date,
                'end_date'                    => $request->end_date,
                'status'                      => $request->status,
                'created_by'                  => auth()->id(),
                'message_template_id'         => $messageTemplateId,
                'message_intro'               => $messageIntro,
                'message_closing'             => $messageClosing,
                'overdue_reminder_mode'       => $overdueMode,
                'overdue_message_template_id' => $overdueTemplateId,
                'overdue_message_intro'       => $overdueIntro,
                'overdue_message_closing'     => $overdueClosing,
            ]);

            // Save contacts
            $this->saveContacts($license, $request->contacts);

            // Handle file uploads
            if ($request->hasFile('files')) {
                $this->uploadFiles($license, $request->file('files'));
            }

            return $license;
        });

        // Generate initial reminder schedules
        $scheduler->generateFor($license);

        AuditLogger::log('license.created', $license, "Lisensi baru {$license->name} berhasil dibuat.");

        return redirect()->route('licenses.index')
            ->with('success', 'Lisensi berhasil ditambahkan.');
    }

    public function show(License $license): View
    {
        $license->load(['files', 'contacts', 'creator', 'reminderLogs']);

        // Get H-0 settings
        $time1 = setting('urgent_alert_time_1') ?: '09:00';
        $time2 = setting('urgent_alert_time_2') ?: '15:00';

        [$hour1, $min1] = explode(':', $time1);
        [$hour2, $min2] = explode(':', $time2);

        $endDate = $license->end_date->copy()->setTimezone('Asia/Jakarta');
        $scheduledAt1 = $endDate->copy()->setTime((int)$hour1, (int)$min1);
        $scheduledAt2 = $endDate->copy()->setTime((int)$hour2, (int)$min2);

        // Fetch urgent logs from database
        $urgentLog1 = \App\Models\UrgentAlertLog::where('license_id', $license->id)
            ->where('alert_date', $license->end_date->toDateString())
            ->where('slot', 'slot_1')
            ->first();

        $urgentLog2 = \App\Models\UrgentAlertLog::where('license_id', $license->id)
            ->where('alert_date', $license->end_date->toDateString())
            ->where('slot', 'slot_2')
            ->first();

        $today = Carbon::today('Asia/Jakarta');
        $isFuture = $license->end_date->greaterThanOrEqualTo($today);

        $isStopped = $license->lifecycle_status === 'stopped';

        // Status for slot 1
        if ($urgentLog1) {
            $status1 = $urgentLog1->status;
            $sentAt1 = $urgentLog1->sent_at;
        } else {
            $status1 = $isStopped ? 'skipped' : ($isFuture ? 'pending' : 'skipped');
            $sentAt1 = null;
        }

        // Status for slot 2
        if ($urgentLog2) {
            $status2 = $urgentLog2->status;
            $sentAt2 = $urgentLog2->sent_at;
        } else {
            $status2 = $isStopped ? 'skipped' : ($isFuture ? 'pending' : 'skipped');
            $sentAt2 = null;
        }

        // Build the two H-0 timeline items
        $h0Item1 = (object)[
            'milestone'    => 'H-0 · Pengiriman 1',
            'label'        => 'H-0 · Pengiriman 1',
            'status'       => $status1,
            'scheduled_at' => $scheduledAt1,
            'sent_at'      => $sentAt1,
            'is_urgent'    => true,
        ];

        $h0Item2 = (object)[
            'milestone'    => 'H-0 · Pengiriman 2',
            'label'        => 'H-0 · Pengiriman 2',
            'status'       => $status2,
            'scheduled_at' => $scheduledAt2,
            'sent_at'      => $sentAt2,
            'is_urgent'    => true,
        ];

        // Map the regular reminder logs
        $timelineItems = $license->reminderLogs->map(function ($log) {
            return (object)[
                'milestone'    => $log->milestone,
                'label'        => $log->milestone >= 0 ? 'H-'.$log->milestone : 'H+'.abs($log->milestone),
                'status'       => $log->status,
                'scheduled_at' => $log->scheduled_at->copy()->setTimezone('Asia/Jakarta'),
                'sent_at'      => $log->sent_at ? $log->sent_at->copy()->setTimezone('Asia/Jakarta') : null,
                'is_urgent'    => false,
            ];
        })->toArray();

        // Add the H-0 items
        $timelineItems[] = $h0Item1;
        $timelineItems[] = $h0Item2;

        // Sort chronologically DESC (so that later dates/times are at the top)
        usort($timelineItems, function ($a, $b) {
            return $b->scheduled_at->timestamp <=> $a->scheduled_at->timestamp;
        });

        return view('licenses.show', compact('license', 'timelineItems'));
    }

    /**
     * Show the form for editing the specified license.
     */
    public function edit(License $license): View
    {
        $license->load(['files', 'contacts']);
        $templates = \App\Models\MessageTemplate::orderBy('name')->get();
        return view('licenses.edit', compact('license', 'templates'));
    }

    /**
     * Update the specified license in storage.
     */
    public function update(UpdateLicenseRequest $request, License $license, ReminderScheduler $scheduler): RedirectResponse
    {
        $oldEndDate = $license->end_date->format('Y-m-d');

        DB::transaction(function () use ($request, $license) {
            $reminderMode = $request->reminder_mode;
            $overdueMode = $request->overdue_reminder_mode;

            $messageTemplateId = $reminderMode === 'template' ? $request->message_template_id : null;
            $messageIntro = $reminderMode === 'custom' ? $request->message_intro : null;
            $messageClosing = $reminderMode === 'custom' ? $request->message_closing : null;

            $overdueTemplateId = $overdueMode === 'template' ? $request->overdue_message_template_id : null;
            $overdueIntro = $overdueMode === 'custom' ? $request->overdue_message_intro : null;
            $overdueClosing = $overdueMode === 'custom' ? $request->overdue_message_closing : null;

            $license->update([
                'name'                        => $request->name,
                'vendor'                      => $request->vendor,
                'description'                 => $request->description,
                'license_key'                 => $request->license_key,
                'start_date'                  => $request->start_date,
                'end_date'                    => $request->end_date,
                'status'                      => $request->status,
                'message_template_id'         => $messageTemplateId,
                'message_intro'               => $messageIntro,
                'message_closing'             => $messageClosing,
                'overdue_reminder_mode'       => $overdueMode,
                'overdue_message_template_id' => $overdueTemplateId,
                'overdue_message_intro'       => $overdueIntro,
                'overdue_message_closing'     => $overdueClosing,
            ]);

            // Replace all contacts
            $license->contacts()->delete();
            $this->saveContacts($license, $request->contacts);

            // Handle new file uploads (existing files remain)
            if ($request->hasFile('files')) {
                $this->uploadFiles($license, $request->file('files'));
            }
        });

        // Regenerate reminder schedules ONLY if end_date changed
        if ($oldEndDate !== $request->end_date) {
            $scheduler->generateFor($license);
        }

        AuditLogger::log('license.updated', $license, "Lisensi {$license->name} diperbarui.");

        return redirect()->route('licenses.show', $license)
            ->with('success', 'Lisensi berhasil diperbarui.');
    }

    /**
     * Permanently delete the specified license and its related data.
     */
    public function destroy(License $license): RedirectResponse
    {
        $licenseName = $license->name;
        $licenseId = $license->id;

        DB::transaction(function () use ($license) {
            $license->delete();
        });

        AuditLogger::log('license.deleted', ['type' => 'App\Models\License', 'id' => $licenseId], "Lisensi {$licenseName} berhasil dihapus.");

        $previousUrl = url()->previous();
        if (str_contains($previousUrl, '/dashboard')) {
            return redirect()->route('dashboard')
                ->with('success', 'Lisensi berhasil dihapus.');
        }

        return redirect()->route('licenses.index')
            ->with('success', 'Lisensi berhasil dihapus.');
    }

    /**
     * Renew the specified license.
     */
    public function renew(Request $request, License $license, ReminderScheduler $scheduler): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ], [
            'start_date.required' => 'Tanggal mulai perpanjangan wajib diisi.',
            'end_date.required'   => 'Tanggal berakhir perpanjangan wajib diisi.',
            'end_date.after'      => 'Tanggal berakhir harus setelah tanggal mulai.',
        ]);

        $oldStartDate = $license->start_date->format('d M Y');
        $oldEndDate = $license->end_date->format('d M Y');

        DB::transaction(function () use ($license, $validated) {
            $license->update([
                'start_date'       => $validated['start_date'],
                'end_date'         => $validated['end_date'],
                'lifecycle_status' => 'active',
                'stopped_at'       => null,
                'stopped_by'       => null,
            ]);
        });

        // Regenerate reminder schedules
        $scheduler->generateFor($license);

        AuditLogger::log('license.renewed', $license, "Lisensi {$license->name} diperpanjang dari periode {$oldStartDate} - {$oldEndDate} menjadi {$license->start_date->format('d M Y')} - {$license->end_date->format('d M Y')}.");

        return redirect()->route('licenses.show', $license)
            ->with('success', 'Lisensi berhasil diperpanjang.');
    }

    /**
     * Stop the specified license.
     */
    public function stop(Request $request, License $license): RedirectResponse
    {
        if ($license->lifecycle_status === 'stopped') {
            return redirect()->route('licenses.show', $license)
                ->with('error', 'Lisensi sudah dalam status dihentikan.');
        }

        DB::transaction(function () use ($license) {
            $license->update([
                'lifecycle_status' => 'stopped',
                'stopped_at'       => Carbon::now('Asia/Jakarta'),
                'stopped_by'       => auth()->id(),
            ]);

            // Skip any pending or queued reminder logs
            $license->reminderLogs()->whereIn('status', ['pending', 'queued'])->update([
                'status' => 'skipped'
            ]);

            // Skip any pending urgent alert logs
            \App\Models\UrgentAlertLog::where('license_id', $license->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'skipped'
                ]);
        });

        AuditLogger::log('license.stopped', $license, "Lisensi {$license->name} dihentikan pemantauannya.");

        return redirect()->route('licenses.show', $license)
            ->with('success', 'Lisensi berhasil dihentikan.');
    }

    /**
     * Reactivate the specified license.
     */
    public function reactivate(Request $request, License $license, ReminderScheduler $scheduler): RedirectResponse
    {
        if ($license->lifecycle_status !== 'stopped') {
            return redirect()->route('licenses.show', $license)
                ->with('error', 'Hanya lisensi yang dihentikan yang dapat diaktifkan kembali.');
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ], [
            'start_date.required' => 'Tanggal mulai aktif kembali wajib diisi.',
            'end_date.required'   => 'Tanggal berakhir aktif kembali wajib diisi.',
            'end_date.after'      => 'Tanggal berakhir harus setelah tanggal mulai.',
        ]);

        DB::transaction(function () use ($license, $validated) {
            $license->update([
                'start_date'       => $validated['start_date'],
                'end_date'         => $validated['end_date'],
                'lifecycle_status' => 'active',
                'stopped_at'       => null,
                'stopped_by'       => null,
            ]);
        });

        // Regenerate reminder schedules
        $scheduler->generateFor($license);

        AuditLogger::log('license.reactivated', $license, "Lisensi {$license->name} diaktifkan kembali dengan masa berlaku {$license->start_date->format('d M Y')} - {$license->end_date->format('d M Y')}.");

        return redirect()->route('licenses.show', $license)
            ->with('success', 'Lisensi berhasil diaktifkan kembali.');
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Save (or replace) contacts for a license.
     * First contact is always primary if none are flagged.
     */
    private function saveContacts(License $license, array $contacts): void
    {
        $hasPrimary = collect($contacts)->contains(fn ($c) => !empty($c['is_primary']));

        foreach ($contacts as $index => $contactData) {
            $license->contacts()->create([
                'name'       => $contactData['name'],
                'phone'      => $contactData['phone'], // already normalized by FormRequest
                'is_primary' => $hasPrimary
                    ? !empty($contactData['is_primary'])
                    : $index === 0, // first is primary if none specified
            ]);
        }
    }

    /**
     * Upload files to private 'licenses' disk.
     * Path format: {license_id}/{uuid}.{ext}
     */
    private function uploadFiles(License $license, array $files): void
    {
        foreach ($files as $file) {
            $extension = $file->getClientOriginalExtension();
            $uuid      = Str::uuid()->toString();
            $path      = "{$license->id}/{$uuid}.{$extension}";

            Storage::disk('licenses')->putFileAs(
                "{$license->id}",
                $file,
                "{$uuid}.{$extension}"
            );

            LicenseFile::create([
                'license_id'    => $license->id,
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);
        }
    }
}
