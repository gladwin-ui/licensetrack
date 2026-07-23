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

        $totalLicenses = License::count();
        $criticalCount = License::expiringWithin(30)->count();
        $expiredCount = License::expired()->count();
        $vendors = License::whereNotNull('vendor')
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
        return view('licenses.create');
    }

    /**
     * Store a newly created license in storage.
     */
    public function store(StoreLicenseRequest $request, ReminderScheduler $scheduler): RedirectResponse
    {
        $license = DB::transaction(function () use ($request) {
            // Create the license record
            $license = License::create([
                'name'        => $request->name,
                'vendor'      => $request->vendor,
                'description' => $request->description,
                'license_key' => $request->license_key,
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'status'      => $request->status,
                'created_by'  => auth()->id(),
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

    /**
     * Display the specified license.
     */
    public function show(License $license): View
    {
        $license->load(['files', 'contacts', 'creator', 'reminderLogs']);
        return view('licenses.show', compact('license'));
    }

    /**
     * Show the form for editing the specified license.
     */
    public function edit(License $license): View
    {
        $license->load(['files', 'contacts']);
        return view('licenses.edit', compact('license'));
    }

    /**
     * Update the specified license in storage.
     */
    public function update(UpdateLicenseRequest $request, License $license, ReminderScheduler $scheduler): RedirectResponse
    {
        $oldEndDate = $license->end_date->format('Y-m-d');

        DB::transaction(function () use ($request, $license) {
            $license->update([
                'name'        => $request->name,
                'vendor'      => $request->vendor,
                'description' => $request->description,
                'license_key' => $request->license_key,
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'status'      => $request->status,
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
            $license->load(['files', 'contacts', 'reminderLogs']);

            $contactIds = $license->contacts->pluck('id');
            $reminderLogIds = $license->reminderLogs->pluck('id');

            DB::table('whatsapp_messages')
                ->where(function ($query) use ($contactIds, $reminderLogIds) {
                    $query->whereIn('license_contact_id', $contactIds)
                        ->orWhereIn('reminder_log_id', $reminderLogIds);
                })
                ->delete();

            Storage::disk('licenses')->deleteDirectory((string) $license->id);

            $license->forceDelete();
        });

        AuditLogger::log('license.deleted', ['type' => 'App\Models\License', 'id' => $licenseId], "Lisensi {$licenseName} berhasil dihapus permanen.");

        $previousUrl = url()->previous();
        if (str_contains($previousUrl, '/dashboard')) {
            return redirect()->route('dashboard')
                ->with('success', 'Lisensi berhasil dihapus permanen.');
        }

        return redirect()->route('licenses.index')
            ->with('success', 'Lisensi berhasil dihapus permanen.');
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
