<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'vendor'      => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'license_key' => ['nullable', 'string', 'max:1000'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after:start_date'],
            'status'      => ['required', Rule::in(['active', 'renewed', 'cancelled'])],

            // Message template fields
            'reminder_mode'       => ['required', 'in:standard,template,custom'],
            'message_template_id' => ['required_if:reminder_mode,template', 'nullable', 'exists:message_templates,id'],
            'message_intro'       => ['required_if:reminder_mode,custom', 'nullable', 'string', 'max:500'],
            'message_closing'     => ['required_if:reminder_mode,custom', 'nullable', 'string', 'max:500'],

            // At least 1 contact required
            'contacts'          => ['required', 'array', 'min:1'],
            'contacts.*.name'   => ['required', 'string', 'max:255'],
            'contacts.*.phone'  => ['required', 'string'],

            // File uploads (optional but validated if present)
            'files'   => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx,zip', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Nama lisensi wajib diisi.',
            'start_date.required'    => 'Tanggal mulai wajib diisi.',
            'end_date.required'      => 'Tanggal berakhir wajib diisi.',
            'end_date.after'         => 'Tanggal berakhir harus setelah tanggal mulai.',
            'status.required'        => 'Status lisensi wajib dipilih.',
            'reminder_mode.required' => 'Mode pengingat wajib dipilih.',
            'message_template_id.required_if' => 'Template pesan wajib dipilih.',
            'message_intro.required_if' => 'Kalimat pembuka kustom wajib diisi.',
            'message_intro.max'      => 'Kalimat pembuka kustom maksimal 500 karakter.',
            'message_closing.required_if' => 'Kalimat penutup kustom wajib diisi.',
            'message_closing.max'    => 'Kalimat penutup kustom maksimal 500 karakter.',
            'contacts.required'      => 'Minimal satu kontak PIC wajib diisi.',
            'contacts.min'           => 'Minimal satu kontak PIC wajib diisi.',
            'contacts.*.name.required'  => 'Nama PIC wajib diisi.',
            'contacts.*.phone.required' => 'Nomor WhatsApp PIC wajib diisi.',
            'files.*.mimes'          => 'Format file tidak didukung. Gunakan: PDF, JPG, PNG, DOC, DOCX, ZIP.',
            'files.*.max'            => 'Ukuran file maksimal 10 MB.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $mode = $this->input('reminder_mode');
            if ($mode === 'custom') {
                $intro = $this->input('message_intro', '');
                $closing = $this->input('message_closing', '');
                
                $invalidIntro = \App\Services\MessagePlaceholderResolver::validate($intro ?? '');
                if (!empty($invalidIntro)) {
                    $validator->errors()->add('message_intro', 'Kalimat pembuka mengandung placeholder yang tidak valid: ' . implode(', ', $invalidIntro) . '. Tersedia: {perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}');
                }
                
                $invalidClosing = \App\Services\MessagePlaceholderResolver::validate($closing ?? '');
                if (!empty($invalidClosing)) {
                    $validator->errors()->add('message_closing', 'Kalimat penutup mengandung placeholder yang tidak valid: ' . implode(', ', $invalidClosing) . '. Tersedia: {perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}');
                }
            }
        });
    }

    /**
     * After validation passes, normalize all contact phone numbers.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function passedValidation(): void
    {
        $contacts = $this->input('contacts', []);
        $normalized = [];

        foreach ($contacts as $index => $contact) {
            try {
                $contact['phone'] = PhoneNumber::normalize($contact['phone']);
            } catch (\InvalidArgumentException $e) {
                $this->failValidation($index, $e->getMessage());
            }
            $normalized[] = $contact;
        }

        // Normalize template properties according to selected mode
        $mode = $this->input('reminder_mode');
        if ($mode === 'standard') {
            $this->merge([
                'message_template_id' => null,
                'message_intro'       => null,
                'message_closing'     => null,
            ]);
        } elseif ($mode === 'template') {
            $this->merge([
                'message_intro'       => null,
                'message_closing'     => null,
            ]);
        } elseif ($mode === 'custom') {
            $this->merge([
                'message_template_id' => null,
            ]);
        }

        $this->merge(['contacts' => $normalized]);
    }

    private function failValidation(int $index, string $message): void
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            "contacts.{$index}.phone" => $message,
        ]);
    }
}
