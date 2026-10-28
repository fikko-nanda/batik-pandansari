<?php

namespace Tests\Support;

use App\Exceptions\GoogleAuthException;
use App\Services\GoogleTokenVerifier;

/**
 * Pemalsu verifier ID Token Google untuk pengujian.
 *
 * Respons ditentukan per id_token: array payload bila dianggap valid,
 * null bila tidak valid. Set $unavailable = true untuk mensimulasikan
 * gangguan jaringan ke Google (502).
 */
class FakeGoogleTokenVerifier extends GoogleTokenVerifier
{
    /** @var array<string, array<string, mixed>|null> */
    public array $responses = [];

    /** @var array<string, array<string, mixed>|null> */
    public array $accessTokenResponses = [];

    public bool $unavailable = false;

    public function verify(string $idToken): ?array
    {
        if ($this->unavailable) {
            throw new GoogleAuthException(
                'Layanan Google tidak dapat dihubungi. Silakan coba lagi nanti.',
                502,
            );
        }

        if (! array_key_exists($idToken, $this->responses)) {
            return null;
        }

        return $this->responses[$idToken];
    }

    public function verifyAccessToken(string $accessToken): ?array
    {
        if ($this->unavailable) {
            throw new GoogleAuthException(
                'Layanan Google tidak dapat dihubungi. Silakan coba lagi nanti.',
                502,
            );
        }

        if (! array_key_exists($accessToken, $this->accessTokenResponses)) {
            return null;
        }

        return $this->accessTokenResponses[$accessToken];
    }
}
