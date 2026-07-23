<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\ReminderLog;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today('Asia/Jakarta');

        // ----------------------------------------------------------------
        // KPI Counts
        // ----------------------------------------------------------------
        $totalLicenses     = License::count();
        $expiringIn30      = License::expiringWithin(30)->count();
        $expiringIn90      = License::whereBetween('end_date', [
            $today,
            $today->copy()->addDays(90),
        ])->count();
        $expiredCount      = License::expired()->count();
        $safeCount         = License::where('end_date', '>', $today->copy()->addDays(90))->count();
        $warningCount      = License::whereBetween('end_date', [
            $today->copy()->addDays(31),
            $today->copy()->addDays(90),
        ])->count();

        // ----------------------------------------------------------------
        // Table Query with filters
        // ----------------------------------------------------------------
        $query = License::with(['contacts' => function ($q) {
            $q->where('is_primary', true)->orWhereIn('id', function ($sub) {
                $sub->selectRaw('MIN(id)')
                    ->from('license_contacts')
                    ->groupBy('license_id');
            });
        }])->orderBy('end_date', 'asc');

        // Filter by health status (maps to date ranges)
        if ($request->filled('health')) {
            match ($request->health) {
                'aman'    => $query->where('end_date', '>', $today->copy()->addDays(90)),
                'waspada' => $query->whereBetween('end_date', [
                    $today->copy()->addDays(31),
                    $today->copy()->addDays(90),
                ]),
                'kritis'  => $query->whereBetween('end_date', [
                    $today->copy()->addDay(),
                    $today->copy()->addDays(30),
                ]),
                'expired' => $query->where('end_date', '<=', $today),
                default   => null,
            };
        }

        // Filter by license status (active/renewed/cancelled)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vendor
        if ($request->filled('vendor')) {
            $query->where('vendor', 'like', '%' . $request->vendor . '%');
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $licenses = $query->paginate(15)->withQueryString();

        // Get distinct vendors for filter dropdown
        $vendors = License::whereNotNull('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->pluck('vendor');

        $statusDistribution = [
            'aman' => $safeCount,
            'waspada' => $warningCount,
            'kritis' => $expiringIn30,
            'expired' => $expiredCount,
        ];

        $upcomingReminders = ReminderLog::with('license')
            ->whereHas('license')
            ->where('status', 'pending')
            ->where('scheduled_at', '>=', now('Asia/Jakarta'))
            ->orderBy('scheduled_at')
            ->limit(3)
            ->get();

        $recentMessages = WhatsappMessage::with(['contact', 'reminderLog.license'])
            ->latest()
            ->limit(3)
            ->get();

        return view('dashboard', compact(
            'totalLicenses',
            'expiringIn30',
            'expiringIn90',
            'expiredCount',
            'safeCount',
            'warningCount',
            'statusDistribution',
            'upcomingReminders',
            'recentMessages',
            'licenses',
            'vendors',
        ));
    }
}
