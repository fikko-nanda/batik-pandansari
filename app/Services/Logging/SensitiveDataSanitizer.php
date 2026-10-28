<?php

namespace App\Services\Logging;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanitasi terpusat sebelum data sensitif disimpan ke tabel log.
 *
 * Tidak pernah menyimpan password, OTP, token, cookie, header Authorization,
 * signature, API key, maupun data kartu. Nilai yang tidak dikenal tetap
 * dipotong panjangnya agar tabel log tidak membengkak.
 */
class SensitiveDataSanitizer
{
    private const REDACTED = '[REDACTED]';

    private const SENSITIVE_KEY_PARTS = [
        'password',
        'passwd',
        'sandi',
        'otp',
        'token',
        'authorization',
        'cookie',
        'secret',
        'api_key',
        'apikey',
        'server_key',
        'client_key',
        'signature',
        'private_key',
        'card_number',
        'cardnumber',
        'cvv',
        'cvc',
        'pin',
        'credential',
    ];

    private const MAX_DEPTH = 5;

    private const MAX_ARRAY_ITEMS = 50;

    private const MAX_STRING_LENGTH = 2000;

    public function sanitize(mixed $value, int $depth = 0): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            return '[TRUNCATED]';
        }

        if (is_array($value)) {
            $result = [];
            $count = 0;

            foreach ($value as $key => $item) {
                if ($count >= self::MAX_ARRAY_ITEMS) {
                    $result['_truncated'] = true;
                    break;
                }

                if (is_string($key) && $this->isSensitiveKey($key)) {
                    $result[$key] = self::REDACTED;
                } else {
                    $result[$key] = $this->sanitize($item, $depth + 1);
                }

                $count++;
            }

            return $result;
        }

        if (is_string($value)) {
            $value = $this->scrubText($value);

            return mb_strlen($value) > self::MAX_STRING_LENGTH
                ? mb_substr($value, 0, self::MAX_STRING_LENGTH).'...[TRUNCATED]'
                : $value;
        }

        if (is_object($value)) {
            if ($value instanceof \DateTimeInterface) {
                return $value->format(DATE_ATOM);
            }

            return '[OBJECT '.class_basename($value).']';
        }

        return $value;
    }

    public function isSensitiveKey(string $key): bool
    {
        $normalized = mb_strtolower($key);

        foreach (self::SENSITIVE_KEY_PARTS as $part) {
            if (str_contains($normalized, $part)) {
                return true;
            }
        }

        return false;
    }

    public function scrubText(string $text): string
    {
        $text = preg_replace(
            '/Bearer\s+[A-Za-z0-9\-._~+\/]+=*/i',
            'Bearer '.self::REDACTED,
            $text
        ) ?? $text;

        $text = preg_replace(
            '/\b(password|passwd|kata_sandi|token|otp|kode_otp|signature|signature_key|api_key|server_key|client_key|secret)\b\s*[=:]\s*[^\s,&;]+/i',
            '$1='.self::REDACTED,
            $text
        ) ?? $text;

        return $text;
    }

    public function maskRecipient(?string $recipient): ?string
    {
        if ($recipient === null || trim($recipient) === '') {
            return null;
        }

        $recipient = trim($recipient);

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            [$local, $domain] = explode('@', $recipient, 2);
            $visible = mb_substr($local, 0, 1);

            return $visible.str_repeat('*', max(2, min(6, mb_strlen($local) - 1))).'@'.$domain;
        }

        $digits = preg_replace('/\D+/', '', $recipient) ?? '';

        if ($digits !== '') {
            return str_repeat('*', max(2, strlen($digits) - 4)).substr($digits, -4);
        }

        $visible = mb_substr($recipient, 0, 1);

        return $visible.str_repeat('*', max(2, mb_strlen($recipient) - 1));
    }

    /**
     * Ringkasan input request tanpa header/cookie/berkas.
     *
     * @return array<string, mixed>|null
     */
    public function summarizeRequest(Request $request): ?array
    {
        $input = array_merge($request->query(), $request->all());

        $input = array_filter(
            $input,
            fn ($value, $key) => ! is_object($value) || $value instanceof \DateTimeInterface,
            ARRAY_FILTER_USE_BOTH
        );

        if ($input === []) {
            return null;
        }

        $sanitized = $this->sanitize($input);

        return is_array($sanitized) && $sanitized !== [] ? $sanitized : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function summarizeResponse(Response $response): ?array
    {
        $contentType = (string) $response->headers->get('content-type', '');

        if (! str_contains($contentType, 'json')) {
            return null;
        }

        $decoded = json_decode((string) $response->getContent(), true);

        if (! is_array($decoded)) {
            return null;
        }

        $sanitized = $this->sanitize($decoded);

        return is_array($sanitized) ? $sanitized : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sanitizeArray(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sanitized = $this->sanitize($data);

        return is_array($sanitized) ? $sanitized : null;
    }
}
