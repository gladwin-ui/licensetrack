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
            'contacts.required'      => 'Minimal satu kontak PIC wajib diisi.',
            'contacts.min'           => 'Minimal satu kontak PIC wajib diisi.',
            'contacts.*.name.required'  => 'Nama PIC wajib diisi.',
            'contacts.*.phone.required' => 'Nomor WhatsApp PIC wajib diisi.',
            'files.*.mimes'          => 'Format file tidak didukung. Gunakan: PDF, JPG, PNG, DOC, DOCX, ZIP.',
            'files.*.max'            => 'Ukuran file maksimal 10 MB.',
        ];
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

        $this->merge(['contacts' => $normalized]);
    }

    private function failValidation(int $index, string $message): void
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            "contacts.{$index}.phone" => $message,
        ]);
    }
}
