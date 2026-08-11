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
            'message_template_id' => ['required_if:reminder_mode,template', 'nullable', Rule::exists('message_templates', 'id')->where('type', 'reminder')],
            'message_intro'       => ['required_if:reminder_mode,custom', 'nullable', 'string', 'max:500'],
            'message_closing'     => ['required_if:reminder_mode,custom', 'nullable', 'string', 'max:500'],

            // Overdue message fields
            'overdue_reminder_mode'       => ['required', 'in:standard,template,custom'],
            'overdue_message_template_id' => ['required_if:overdue_reminder_mode,template', 'nullable', Rule::exists('message_templates', 'id')->where('type', 'overdue')],
            'overdue_message_intro'       => ['required_if:overdue_reminder_mode,custom', 'nullable', 'string', 'max:500'],
            'overdue_message_closing'     => ['required_if:overdue_reminder_mode,custom', 'nullable', 'string', 'max:500'],

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

            'overdue_reminder_mode.required' => 'Mode pengingat setelah kedaluwarsa wajib dipilih.',
            'overdue_message_template_id.required_if' => 'Template pesan setelah kedaluwarsa wajib dipilih.',
            'overdue_message_intro.required_if' => 'Kalimat pembuka kustom setelah kedaluwarsa wajib diisi.',
            'overdue_message_intro.max'      => 'Kalimat pembuka kustom setelah kedaluwarsa maksimal 500 karakter.',
            'overdue_message_closing.required_if' => 'Kalimat penutup kustom setelah kedaluwarsa wajib diisi.',
            'overdue_message_closing.max'    => 'Kalimat penutup kustom setelah kedaluwarsa maksimal 500 karakter.',

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
                
                $invalidIntro = \App\Services\MessagePlaceholderResolver::validate($intro ?? '', 'reminder');
                if (!empty($invalidIntro)) {
                    $errorMsg = 'Kalimat pembuka mengandung placeholder yang tidak valid: ' . implode(', ', $invalidIntro) . '. Tersedia: {perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}';
                    if (in_array('{hari_terlewat}', $invalidIntro)) {
                        $errorMsg = 'Placeholder {hari_terlewat} hanya dapat digunakan untuk template setelah kedaluwarsa.';
                    }
                    $validator->errors()->add('message_intro', $errorMsg);
                }
                
                $invalidClosing = \App\Services\MessagePlaceholderResolver::validate($closing ?? '', 'reminder');
                if (!empty($invalidClosing)) {
                    $errorMsg = 'Kalimat penutup mengandung placeholder yang tidak valid: ' . implode(', ', $invalidClosing) . '. Tersedia: {perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {sisa_hari}';
                    if (in_array('{hari_terlewat}', $invalidClosing)) {
                        $errorMsg = 'Placeholder {hari_terlewat} hanya dapat digunakan untuk template setelah kedaluwarsa.';
                    }
                    $validator->errors()->add('message_closing', $errorMsg);
                }
            }

            $overdueMode = $this->input('overdue_reminder_mode');
            if ($overdueMode === 'custom') {
                $overdueIntro = $this->input('overdue_message_intro', '');
                $overdueClosing = $this->input('overdue_message_closing', '');
                
                $invalidOverdueIntro = \App\Services\MessagePlaceholderResolver::validate($overdueIntro ?? '', 'overdue');
                if (!empty($invalidOverdueIntro)) {
                    $errorMsg = 'Kalimat pembuka mengandung placeholder yang tidak valid: ' . implode(', ', $invalidOverdueIntro) . '. Tersedia: {perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {hari_terlewat}';
                    if (in_array('{sisa_hari}', $invalidOverdueIntro)) {
                        $errorMsg = 'Placeholder {sisa_hari} hanya dapat digunakan untuk template pengingat sebelum kedaluwarsa.';
                    }
                    $validator->errors()->add('overdue_message_intro', $errorMsg);
                }
                
                $invalidOverdueClosing = \App\Services\MessagePlaceholderResolver::validate($overdueClosing ?? '', 'overdue');
                if (!empty($invalidOverdueClosing)) {
                    $errorMsg = 'Kalimat penutup mengandung placeholder yang tidak valid: ' . implode(', ', $invalidOverdueClosing) . '. Tersedia: {perusahaan}, {nama_pic}, {nama_lisensi}, {vendor}, {tanggal_mulai}, {tanggal_berakhir}, {hari_terlewat}';
                    if (in_array('{sisa_hari}', $invalidOverdueClosing)) {
                        $errorMsg = 'Placeholder {sisa_hari} hanya dapat digunakan untuk template pengingat sebelum kedaluwarsa.';
                    }
                    $validator->errors()->add('overdue_message_closing', $errorMsg);
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
