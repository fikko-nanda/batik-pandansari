<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UniqueWhatsAppNumber implements ValidationRule
{
    public function __construct(private readonly ?string $ignoreUserId = null) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $normalized = User::normalizeWhatsAppNumber($value);
        $variants = self::variants($value);

        $query = User::where(function ($query) use ($normalized, $variants) {
            $query->whereIn('nomor_telepon', $variants);

            if ($normalized !== null) {
                $query->orWhere('nomor_telepon_normalized', $normalized);
            }
        });

        if ($this->ignoreUserId !== null) {
            $query->where('id_user', '!=', $this->ignoreUserId);
        }

        if ($query->exists()) {
            $fail('Nomor WhatsApp sudah terdaftar. Gunakan nomor lain.');
        }
    }

    /**
     * Bentuk nomor yang dianggap sama (lokal 08... dan internasional 62...).
     *
     * @return array<int, string>
     */
    public static function variants(string $value): array
    {
        $normalized = User::normalizeWhatsAppNumber($value);

        if ($normalized === null) {
            return [$value];
        }

        $lokal = str_starts_with($normalized, '62')
            ? '0'.substr($normalized, 2)
            : $normalized;

        return array_values(array_unique([$value, $normalized, $lokal]));
    }
}
