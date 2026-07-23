<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\LicenseFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\AuditLogger;

class LicenseFileController extends Controller
{
    /**
     * Download a license file from the private disk.
     * Route: GET /licenses/{license}/files/{file}/download
     * Protected by 'auth' middleware via route definition.
     */
    public function download(License $license, LicenseFile $file): StreamedResponse
    {
        // Ensure the file belongs to this license
        abort_if($file->license_id !== $license->id, 404);

        AuditLogger::log('file.downloaded', $file, "Mengunduh file: {$file->original_name} dari lisensi {$license->name}.");

        return Storage::disk('licenses')->download(
            $file->path,
            $file->original_name
        );
    }

    /**
     * Delete a license file from storage and the database.
     * Route: DELETE /licenses/{license}/files/{file}
     */
    public function destroy(License $license, LicenseFile $file): RedirectResponse
    {
        // Ensure the file belongs to this license
        abort_if($file->license_id !== $license->id, 404);

        $fileName = $file->original_name;
        $fileId = $file->id;

        // Remove from private disk
        Storage::disk('licenses')->delete($file->path);

        // Remove from database
        $file->delete();

        AuditLogger::log('file.deleted', ['type' => 'App\Models\LicenseFile', 'id' => $fileId], "Menghapus file: {$fileName} dari lisensi {$license->name}.");

        return redirect()->route('licenses.show', $license)
            ->with('success', 'File berhasil dihapus.');
    }
}
