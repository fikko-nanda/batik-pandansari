<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class WhatsAppNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (! preg_match('/^[0-9]+$/', $value)) {
            $fail('Nomor WhatsApp hanya boleh berisi angka 0-9 tanpa spasi, tanda plus, atau tanda baca.');
        }

        $length = strlen($value);

        if ($length < 11 || $length > 13) {
            $fail('Nomor WhatsApp harus terdiri dari 11 sampai 13 digit.');
        }
    }
}
