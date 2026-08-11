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
        $type = $request->input('type', 'reminder');
        $request->validate([
            'type'    => ['required', 'in:reminder,overdue'],
            'name'    => [
                'required', 
                'string', 
                'max:100', 
                \Illuminate\Validation\Rule::unique('message_templates', 'name')->where('type', $type)
            ],
            'intro'   => ['required', 'string', 'max:500'],
            'closing' => ['required', 'string', 'max:500'],
        ], [
            'type.required'    => 'Jenis template wajib dipilih.',
            'type.in'          => 'Jenis template tidak valid.',
            'name.required'    => 'Nama template wajib diisi.',
            'name.unique'      => 'Nama template sudah digunakan untuk jenis ini.',
            'intro.required'   => 'Kalimat pembuka wajib diisi.',
            'intro.max'        => 'Kalimat pembuka maksimal 500 karakter.',
            'closing.required' => 'Kalimat penutup wajib diisi.',
            'closing.max'      => 'Kalimat penutup maksimal 500 karakter.',
        ]);

        // Validate placeholders
        $invalidIntro = MessagePlaceholderResolver::validate($request->intro, $type);
        if (!empty($invalidIntro)) {
            $errorMsg = 'Kalimat pembuka mengandung placeholder yang tidak valid: ' . implode(', ', $invalidIntro) . '. Tersedia: ' . ($type === 'overdue' ? '{perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {hari_terlewat}' : '{perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}');
            if ($type === 'overdue' && in_array('{sisa_hari}', $invalidIntro)) {
                $errorMsg = 'Placeholder {sisa_hari} hanya dapat digunakan untuk template pengingat sebelum kedaluwarsa.';
            } elseif ($type === 'reminder' && in_array('{hari_terlewat}', $invalidIntro)) {
                $errorMsg = 'Placeholder {hari_terlewat} hanya dapat digunakan untuk template setelah kedaluwarsa.';
            }
            return back()->withInput()->withErrors([
                'intro' => $errorMsg
            ]);
        }

        $invalidClosing = MessagePlaceholderResolver::validate($request->closing, $type);
        if (!empty($invalidClosing)) {
            $errorMsg = 'Kalimat penutup mengandung placeholder yang tidak valid: ' . implode(', ', $invalidClosing) . '. Tersedia: ' . ($type === 'overdue' ? '{perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {hari_terlewat}' : '{perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}');
            if ($type === 'overdue' && in_array('{sisa_hari}', $invalidClosing)) {
                $errorMsg = 'Placeholder {sisa_hari} hanya dapat digunakan untuk template pengingat sebelum kedaluwarsa.';
            } elseif ($type === 'reminder' && in_array('{hari_terlewat}', $invalidClosing)) {
                $errorMsg = 'Placeholder {hari_terlewat} hanya dapat digunakan untuk template setelah kedaluwarsa.';
            }
            return back()->withInput()->withErrors([
                'closing' => $errorMsg
            ]);
        }

        $template = MessageTemplate::create([
            'name'       => $request->name,
            'intro'      => $request->intro,
            'closing'    => $request->closing,
            'is_default' => false,
            'created_by' => Auth::id(),
            'type'       => $type,
        ]);

        AuditLogger::log('message_template.created', $template, "Template pesan {$type} baru dibuat: {$template->name}");

        return redirect()->route('settings.index')->with('status', "Template '{$template->name}' berhasil ditambahkan.");
    }

    /**
     * Update the specified message template.
     */
    public function update(Request $request, MessageTemplate $template): RedirectResponse
    {
        $type = $template->type;
        $request->validate([
            'name'    => [
                'required', 
                'string', 
                'max:100', 
                \Illuminate\Validation\Rule::unique('message_templates', 'name')->where('type', $type)->ignore($template->id)
            ],
            'intro'   => ['required', 'string', 'max:500'],
            'closing' => ['required', 'string', 'max:500'],
        ], [
            'name.required'    => 'Nama template wajib diisi.',
            'name.unique'      => 'Nama template sudah digunakan untuk jenis ini.',
            'intro.required'   => 'Kalimat pembuka wajib diisi.',
            'intro.max'        => 'Kalimat pembuka maksimal 500 karakter.',
            'closing.required' => 'Kalimat penutup wajib diisi.',
            'closing.max'      => 'Kalimat penutup maksimal 500 karakter.',
        ]);

        // Validate placeholders
        $invalidIntro = MessagePlaceholderResolver::validate($request->intro, $type);
        if (!empty($invalidIntro)) {
            $errorMsg = 'Kalimat pembuka mengandung placeholder yang tidak valid: ' . implode(', ', $invalidIntro) . '. Tersedia: ' . ($type === 'overdue' ? '{perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {hari_terlewat}' : '{perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}');
            if ($type === 'overdue' && in_array('{sisa_hari}', $invalidIntro)) {
                $errorMsg = 'Placeholder {sisa_hari} hanya dapat digunakan untuk template pengingat sebelum kedaluwarsa.';
            } elseif ($type === 'reminder' && in_array('{hari_terlewat}', $invalidIntro)) {
                $errorMsg = 'Placeholder {hari_terlewat} hanya dapat digunakan untuk template setelah kedaluwarsa.';
            }
            return back()->withInput()->withErrors([
                'intro' => $errorMsg
            ]);
        }

        $invalidClosing = MessagePlaceholderResolver::validate($request->closing, $type);
        if (!empty($invalidClosing)) {
            $errorMsg = 'Kalimat penutup mengandung placeholder yang tidak valid: ' . implode(', ', $invalidClosing) . '. Tersedia: ' . ($type === 'overdue' ? '{perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {hari_terlewat}' : '{perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}');
            if ($type === 'overdue' && in_array('{sisa_hari}', $invalidClosing)) {
                $errorMsg = 'Placeholder {sisa_hari} hanya dapat digunakan untuk template pengingat sebelum kedaluwarsa.';
            } elseif ($type === 'reminder' && in_array('{hari_terlewat}', $invalidClosing)) {
                $errorMsg = 'Placeholder {hari_terlewat} hanya dapat digunakan untuk template setelah kedaluwarsa.';
            }
            return back()->withInput()->withErrors([
                'closing' => $errorMsg
            ]);
        }

        $template->update([
            'name'    => $request->name,
            'intro'   => $request->intro,
            'closing' => $request->closing,
        ]);

        AuditLogger::log('message_template.updated', $template, "Template pesan {$type} diperbarui: {$template->name}");

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
        $usageCount = $template->type === 'overdue' 
            ? $template->overdueLicenses()->count()
            : $template->licenses()->count();

        if ($usageCount > 0) {
            return redirect()->route('settings.index')
                ->with('error', "Template '{$template->name}' tidak dapat dihapus karena sedang digunakan oleh {$usageCount} lisensi. Pindahkan lisensi terlebih dahulu.");
        }

        $templateName = $template->name;
        $templateId = $template->id;
        $templateType = $template->type;

        $template->delete();

        AuditLogger::log('message_template.deleted', ['type' => 'App\Models\MessageTemplate', 'id' => $templateId], "Template pesan {$templateType} dihapus: {$templateName}");

        return redirect()->route('settings.index')->with('status', "Template '{$templateName}' berhasil dihapus.");
    }

    /**
     * Set the specified template as default.
     */
    public function setDefault(MessageTemplate $template): RedirectResponse
    {
        DB::transaction(function () use ($template) {
            // Remove default status from all templates of the same type
            MessageTemplate::where('type', $template->type)->where('is_default', true)->update(['is_default' => false]);
            
            // Set current as default
            $template->is_default = true;
            $template->save();
        });

        AuditLogger::log('message_template.set_default', $template, "Template {$template->type} '{$template->name}' dijadikan template default.");

        return redirect()->route('settings.index')->with('status', "Template '{$template->name}' sekarang menjadi template default.");
    }
}
