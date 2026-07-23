<?php

namespace App\Support;

use InvalidArgumentException;

class PhoneNumber
{
    /**
     * Normalize a raw phone number string to the format 628xxxxxxxxx.
     *
     * Rules:
     *  1. Strip all non-digit characters (spaces, dashes, parentheses, +, etc.)
     *  2. If starts with '0'  → replace leading 0 with '62'
     *  3. If starts with '8'  → prepend '62'
     *  4. If starts with '62' → leave as-is
     *
     * Validation: result must be all digits, length 10-15.
     *
     * @throws InvalidArgumentException if the normalized number is invalid
     */
    public static function normalize(string $raw): string
    {
        // Step 1: remove all non-digit characters
        $digits = preg_replace('/\D/', '', $raw);

        if ($digits === null || $digits === '') {
            throw new InvalidArgumentException("Nomor telepon tidak valid: '{$raw}'");
        }

        // Step 2-4: apply prefix rules
        if (str_starts_with($digits, '0')) {
            // Replace leading 0 with 62
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            // Prepend 62
            $digits = '62' . $digits;
        } elseif (str_starts_with($digits, '62')) {
            // Already in correct format, leave as-is
        } else {
            // Other prefixes — try prepending 62 (international format)
            $digits = '62' . $digits;
        }

        // Validate: all digits, length 10-15
        if (!ctype_digit($digits) || strlen($digits) < 10 || strlen($digits) > 15) {
            throw new InvalidArgumentException(
                "Nomor telepon tidak valid setelah normalisasi: '{$digits}' (panjang harus 10-15 digit)"
            );
        }

        return $digits;
    }
}
