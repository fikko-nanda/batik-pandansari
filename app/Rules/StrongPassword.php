<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class StrongPassword implements ValidationRule
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

        if (mb_strlen($value) < 8) {
            $fail('Kata sandi minimal 8 karakter.');
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail('Kata sandi harus mengandung minimal 1 huruf besar.');
        }

        if (! preg_match('/[a-z]/', $value)) {
            $fail('Kata sandi harus mengandung minimal 1 huruf kecil.');
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail('Kata sandi harus mengandung minimal 1 angka.');
        }

        if (! preg_match('/[@#$%]/', $value)) {
            $fail('Kata sandi harus mengandung minimal 1 simbol dari @, #, $, atau %.');
        }
    }
}
