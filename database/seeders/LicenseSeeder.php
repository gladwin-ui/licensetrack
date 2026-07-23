<?php

namespace Database\Seeders;

use App\Models\License;
use App\Models\LicenseContact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LicenseSeeder extends Seeder
{
    /**
     * Demo data with end_dates spread across all health status categories:
     * - 1 expired (20 days ago)
     * - 1 expired today
     * - 2 kritis   (H-5, H-25)
     * - 2 waspada  (H-45, H-80)
     * - 3 aman     (H-120, H-200, H-350)
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@hariff.co.id')->first();
        $today = Carbon::today('Asia/Jakarta');

        $licenses = [
            // ----- Expired (healthStatus = 'expired') -----
            [
                'name'        => 'Antivirus Kaspersky Endpoint Security',
                'vendor'      => 'Kaspersky Lab',
                'description' => 'Lisensi antivirus untuk 50 endpoint workstation.',
                'license_key' => 'KSEP-2024-XXXX-ABCD-1234',
                'start_date'  => $today->copy()->subYear(),
                'end_date'    => $today->copy()->subDays(20),
                'status'      => 'active',
                'contacts'    => [
                    ['name' => 'Budi Santoso', 'phone' => '6281234567890', 'is_primary' => true],
                    ['name' => 'Rina Kusuma',  'phone' => '6285678901234', 'is_primary' => false],
                ],
            ],
            [
                'name'        => 'Microsoft 365 Business Standard',
                'vendor'      => 'Microsoft Corporation',
                'description' => 'Paket Office 365 untuk 25 pengguna.',
                'license_key' => 'M365-BST-2024-7890-WXYZ',
                'start_date'  => $today->copy()->subYear(),
                'end_date'    => $today->copy(),
                'status'      => 'active',
                'contacts'    => [
                    ['name' => 'Dian Pratiwi', 'phone' => '6282233445566', 'is_primary' => true],
                ],
            ],

            // ----- Kritis: 1-30 hari (healthStatus = 'kritis') -----
            [
                'name'        => 'Fortinet FortiGate Firewall License',
                'vendor'      => 'Fortinet Inc.',
                'description' => 'Lisensi UTM untuk firewall FortiGate 200F.',
                'license_key' => 'FGT-200F-2024-LIC-5678',
                'start_date'  => $today->copy()->subYear(),
                'end_date'    => $today->copy()->addDays(5),
                'status'      => 'active',
                'contacts'    => [
                    ['name' => 'Agus Setiawan', 'phone' => '6281122334455', 'is_primary' => true],
                    ['name' => 'Sari Dewi',     'phone' => '6289988776655', 'is_primary' => false],
                ],
            ],
            [
                'name'        => 'Adobe Creative Cloud Team',
                'vendor'      => 'Adobe Systems',
                'description' => 'Lisensi Adobe CC untuk tim desain (10 kursi).',
                'license_key' => 'ADCC-TEAM-2024-EFGH-9012',
                'start_date'  => $today->copy()->subYear(),
                'end_date'    => $today->copy()->addDays(25),
                'status'      => 'active',
                'contacts'    => [
                    ['name' => 'Fitri Handayani', 'phone' => '6287766554433', 'is_primary' => true],
                ],
            ],

            // ----- Waspada: 31-90 hari (healthStatus = 'waspada') -----
            [
                'name'        => 'Oracle Database Enterprise Edition',
                'vendor'      => 'Oracle Corporation',
                'description' => 'Lisensi Oracle DB untuk sistem ERP produksi.',
                'license_key' => 'ORA-DB-EE-2024-IJKL-3456',
                'start_date'  => $today->copy()->subYear(),
                'end_date'    => $today->copy()->addDays(45),
                'status'      => 'active',
                'contacts'    => [
                    ['name' => 'Hendro Wicaksono', 'phone' => '6281355667788', 'is_primary' => true],
                    ['name' => 'Laila Nur',         'phone' => '6285544332211', 'is_primary' => false],
                ],
            ],
            [
                'name'        => 'VMware vSphere Enterprise Plus',
                'vendor'      => 'VMware Inc.',
                'description' => 'Lisensi virtualisasi untuk 8 host server.',
                'license_key' => 'VMW-VSP-EP-2024-MNOP-7890',
                'start_date'  => $today->copy()->subYear(),
                'end_date'    => $today->copy()->addDays(80),
                'status'      => 'active',
                'contacts'    => [
                    ['name' => 'Joko Susilo', 'phone' => '6282199887766', 'is_primary' => true],
                ],
            ],

            // ----- Aman: >90 hari (healthStatus = 'aman') -----
            [
                'name'        => 'Autodesk AutoCAD LT 2025',
                'vendor'      => 'Autodesk Inc.',
                'description' => 'Lisensi AutoCAD untuk tim engineering (5 seat).',
                'license_key' => 'ACAD-LT-2025-QRST-1234',
                'start_date'  => $today->copy()->subMonths(2),
                'end_date'    => $today->copy()->addDays(120),
                'status'      => 'active',
                'contacts'    => [
                    ['name' => 'Mira Anggraeni', 'phone' => '6281566778899', 'is_primary' => true],
                    ['name' => 'Rudi Hartono',   'phone' => '6285100112233', 'is_primary' => false],
                ],
            ],
            [
                'name'        => 'Cisco Webex Business Subscription',
                'vendor'      => 'Cisco Systems',
                'description' => 'Layanan video conference Cisco Webex 50 host.',
                'license_key' => 'CWBX-BIZ-2025-UVWX-5678',
                'start_date'  => $today->copy()->subMonth(),
                'end_date'    => $today->copy()->addDays(200),
                'status'      => 'active',
                'contacts'    => [
                    ['name' => 'Nadia Cahyani', 'phone' => '6281677889900', 'is_primary' => true],
                ],
            ],
            [
                'name'        => 'Symantec Endpoint Protection Manager',
                'vendor'      => 'Broadcom (Symantec)',
                'description' => 'Manajemen endpoint security untuk 100 perangkat.',
                'license_key' => 'SEP-MGR-2025-YZAB-9012',
                'start_date'  => $today->copy()->subMonth(),
                'end_date'    => $today->copy()->addDays(350),
                'status'      => 'active',
                'contacts'    => [
                    ['name' => 'Oki Firmansyah', 'phone' => '6283788990011', 'is_primary' => true],
                    ['name' => 'Tika Rahayu',    'phone' => '6281400223344', 'is_primary' => false],
                ],
            ],
        ];

        foreach ($licenses as $data) {
            $contacts = $data['contacts'];
            unset($data['contacts']);

            $license = License::create(array_merge($data, [
                'created_by' => $admin->id,
            ]));

            foreach ($contacts as $contact) {
                LicenseContact::create(array_merge($contact, [
                    'license_id' => $license->id,
                ]));
            }
        }
    }
}
