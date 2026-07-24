<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use App\Services\AuditLogger;
use App\Services\MessagePlaceholderResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageTemplateController extends Controller
{
    /**
     * Store a newly created message template.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:100', 'unique:message_templates,name'],
            'intro'   => ['required', 'string', 'max:500'],
            'closing' => ['required', 'string', 'max:500'],
        ], [
            'name.required'    => 'Nama template wajib diisi.',
            'name.unique'      => 'Nama template sudah digunakan.',
            'intro.required'   => 'Kalimat pembuka wajib diisi.',
            'intro.max'        => 'Kalimat pembuka maksimal 500 karakter.',
            'closing.required' => 'Kalimat penutup wajib diisi.',
            'closing.max'      => 'Kalimat penutup maksimal 500 karakter.',
        ]);

        // Validate placeholders
        $invalidIntro = MessagePlaceholderResolver::validate($request->intro);
        if (!empty($invalidIntro)) {
            return back()->withInput()->withErrors([
                'intro' => 'Kalimat pembuka mengandung placeholder yang tidak valid: ' . implode(', ', $invalidIntro) . '. Tersedia: {perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}'
            ]);
        }

        $invalidClosing = MessagePlaceholderResolver::validate($request->closing);
        if (!empty($invalidClosing)) {
            return back()->withInput()->withErrors([
                'closing' => 'Kalimat penutup mengandung placeholder yang tidak valid: ' . implode(', ', $invalidClosing) . '. Tersedia: {perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}'
            ]);
        }

        $template = MessageTemplate::create([
            'name'       => $request->name,
            'intro'      => $request->intro,
            'closing'    => $request->closing,
            'is_default' => false,
            'created_by' => Auth::id(),
        ]);

        AuditLogger::log('message_template.created', $template, "Template pesan baru dibuat: {$template->name}");

        return redirect()->route('settings.index')->with('status', "Template '{$template->name}' berhasil ditambahkan.");
    }

    /**
     * Update the specified message template.
     */
    public function update(Request $request, MessageTemplate $template): RedirectResponse
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:100', 'unique:message_templates,name,' . $template->id],
            'intro'   => ['required', 'string', 'max:500'],
            'closing' => ['required', 'string', 'max:500'],
        ], [
            'name.required'    => 'Nama template wajib diisi.',
            'name.unique'      => 'Nama template sudah digunakan.',
            'intro.required'   => 'Kalimat pembuka wajib diisi.',
            'intro.max'        => 'Kalimat pembuka maksimal 500 karakter.',
            'closing.required' => 'Kalimat penutup wajib diisi.',
            'closing.max'      => 'Kalimat penutup maksimal 500 karakter.',
        ]);

        // Validate placeholders
        $invalidIntro = MessagePlaceholderResolver::validate($request->intro);
        if (!empty($invalidIntro)) {
            return back()->withInput()->withErrors([
                'intro' => 'Kalimat pembuka mengandung placeholder yang tidak valid: ' . implode(', ', $invalidIntro) . '. Tersedia: {perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}'
            ]);
        }

        $invalidClosing = MessagePlaceholderResolver::validate($request->closing);
        if (!empty($invalidClosing)) {
            return back()->withInput()->withErrors([
                'closing' => 'Kalimat penutup mengandung placeholder yang tidak valid: ' . implode(', ', $invalidClosing) . '. Tersedia: {perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}'
            ]);
        }

        $template->update([
            'name'    => $request->name,
            'intro'   => $request->intro,
            'closing' => $request->closing,
        ]);

        AuditLogger::log('message_template.updated', $template, "Template pesan diperbarui: {$template->name}");

        return redirect()->route('settings.index')->with('status', "Template '{$template->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified message template.
     */
    public function destroy(MessageTemplate $template): RedirectResponse
    {
        if ($template->is_default) {
            return redirect()->route('settings.index')->with('error', "Template default tidak dapat dihapus.");
        }

        // Count how many licenses are currently using this template
        $usageCount = $template->licenses()->count();
        if ($usageCount > 0) {
            return redirect()->route('settings.index')
                ->with('error', "Template '{$template->name}' tidak dapat dihapus karena sedang digunakan oleh {$usageCount} lisensi. Pindahkan lisensi terlebih dahulu.");
        }

        $templateName = $template->name;
        $templateId = $template->id;

        $template->delete();

        AuditLogger::log('message_template.deleted', ['type' => 'App\Models\MessageTemplate', 'id' => $templateId], "Template pesan dihapus: {$templateName}");

        return redirect()->route('settings.index')->with('status', "Template '{$templateName}' berhasil dihapus.");
    }

    /**
     * Set the specified template as default.
     */
    public function setDefault(MessageTemplate $template): RedirectResponse
    {
        DB::transaction(function () use ($template) {
            // Remove default status from all templates
            MessageTemplate::where('is_default', true)->update(['is_default' => false]);
            
            // Set current as default
            $template->is_default = true;
            $template->save();
        });

        AuditLogger::log('message_template.set_default', $template, "Template '{$template->name}' dijadikan template default.");

        return redirect()->route('settings.index')->with('status', "Template '{$template->name}' sekarang menjadi template default.");
    }
}
