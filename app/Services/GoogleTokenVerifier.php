<?php

namespace App\Services;

use App\Exceptions\GoogleAuthException;
use Firebase\JWT\JWT;
use Google_Client;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientExceptionInterface;
use UnexpectedValueException;

/**
 * Verifier identitas Google (OAuth 2.0 / Sign-In with Google).
 *
 * Mendukung dua bentuk kredensial dari klien:
 * - ID Token (JWT) → diverifikasi tanda tangannya terhadap JWKS resmi Google.
 * - Access Token (OAuth) → diverifikasi ke endpoint Google (tokeninfo +
 *   userinfo). Cocok untuk frontend yang memakai alur OAuth implicit/popup.
 *
 * Keamanan yang dilakukan:
 * - Signature JWT diverifikasi terhadap JWKS resmi Google
 *   (https://www.googleapis.com/oauth2/v3/certs).
 * - `aud` wajib cocok dengan GOOGLE_CLIENT_ID.
 * - `iss` wajib accounts.google.com atau https://accounts.google.com.
 * - `exp` dicek ulang (token kedaluwarsa ditolak).
 * - `sub` dan `email` wajib ada.
 *
 * Token tidak valid/rusak mengembalikan null (klien mendapat 401);
 * gangguan jaringan ke Google melempar GoogleAuthException (502).
 */
class GoogleTokenVerifier
{
    public const CLOCK_SKEW_LEEWAY_SECONDS = 60;

    private const JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    private const TOKENINFO_URL = 'https://www.googleapis.com/oauth2/v3/tokeninfo';

    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    /**
     * @return array<string, mixed>|null payload bila valid; null bila token tidak valid/rusak
     *
     * @throws GoogleAuthException bila layanan Google tidak dapat dihubungi
     */
    public function verify(string $idToken): ?array
    {
        $clientId = (string) config('services.google.client_id');

        $jwt = new class extends JWT
        {
            public static $leeway = GoogleTokenVerifier::CLOCK_SKEW_LEEWAY_SECONDS;
        };

        $client = new Google_Client(['jwt' => $jwt]);
        $client->setClientId($clientId);

        try {
            $payload = $client->verifyIdToken($idToken);
        } catch (UnexpectedValueException) {
            // Token bukan JWT yang valid strukturnya — kesalahan klien.
            return null;
        } catch (GuzzleException|ClientExceptionInterface) {
            throw new GoogleAuthException(
                'Layanan Google tidak dapat dihubungi. Silakan coba lagi nanti.',
                502,
            );
        }

        if (! is_array($payload)) {
            return null;
        }

        // Pemeriksaan ganda aud/iss/exp — verifyIdToken sudah memeriksanya,
        // dipastikan ulang agar tidak ada celah bila perilaku library berubah.
        if (($payload['aud'] ?? null) !== $clientId) {
            return null;
        }

        if (! in_array($payload['iss'] ?? null, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            return null;
        }

        if (
            ! isset($payload['exp'])
            || ! is_numeric($payload['exp'])
            || time() >= (int) $payload['exp'] + self::CLOCK_SKEW_LEEWAY_SECONDS
        ) {
            return null;
        }

        if (empty($payload['sub']) || empty($payload['email'])) {
            return null;
        }

        return $payload;
    }

    /**
     * Verifikasi Access Token OAuth Google lalu kembalikan profil pengguna.
     *
     * Langkah:
     * 1. tokeninfo → memastikan token diterbitkan untuk Client ID kita (`aud`)
     *    dan belum kedaluwarsa. (Endpoint tokeninfo tidak memerlukan secret.)
     * 2. userinfo → mengambil `sub`, `email`, `email_verified`, `name`,
     *    `picture` milik pemilik token.
     *
     * Profil yang dikembalikan dinormalkan agar sama dengan payload ID Token
     * (memuat aud/iss/exp) sehingga controller dapat memakainya seragam.
     *
     * @return array<string, mixed>|null
     *
     * @throws GoogleAuthException bila layanan Google tidak dapat dihubungi
     */
    public function verifyAccessToken(string $accessToken): ?array
    {
        $clientId = (string) config('services.google.client_id');

        if (trim($accessToken) === '') {
            return null;
        }

        $http = new Client([
            'timeout' => 10,
            // Jangan lempar pada 4xx (token tidak valid) — tangani manual.
            'http_errors' => false,
        ]);

        try {
            $tokenInfoResponse = $http->get(self::TOKENINFO_URL, [
                'query' => ['access_token' => $accessToken],
            ]);
            $userInfoResponse = $http->get(self::USERINFO_URL, [
                'headers' => ['Authorization' => 'Bearer '.$accessToken],
            ]);
        } catch (GuzzleException) {
            // error jaringan/jaringan ke Google → 502
            throw new GoogleAuthException(
                'Layanan Google tidak dapat dihubungi. Silakan coba lagi nanti.',
                502,
            );
        }

        // Token tidak valid/kedaluwarsa: Google membalas 400/401.
        if ($tokenInfoResponse->getStatusCode() !== 200 || $userInfoResponse->getStatusCode() !== 200) {
            return null;
        }

        $tokenInfo = json_decode((string) $tokenInfoResponse->getBody(), true);
        $userInfo = json_decode((string) $userInfoResponse->getBody(), true);

        if (! is_array($tokenInfo) || ! is_array($userInfo)) {
            return null;
        }

        // tokeninfo mengembalikan `aud` (client id) — pastikan cocok.
        if (($tokenInfo['aud'] ?? null) !== $clientId) {
            return null;
        }

        // Google mengembalikan aud dalam bentuk array di beberapa respons.
        if (is_array($tokenInfo['aud'] ?? null) && ! in_array($clientId, $tokenInfo['aud'], true)) {
            return null;
        }

        if (empty($tokenInfo['exp']) || time() >= (int) $tokenInfo['exp']) {
            return null;
        }

        $sub = (string) ($userInfo['sub'] ?? '');
        $email = (string) ($userInfo['email'] ?? '');

        if ($sub === '' || $email === '') {
            return null;
        }

        return [
            'aud' => $clientId,
            'iss' => 'https://accounts.google.com',
            'exp' => (int) $tokenInfo['exp'],
            'sub' => $sub,
            'email' => $email,
            'email_verified' => $userInfo['email_verified'] ?? ($tokenInfo['email_verified'] ?? false),
            'name' => $userInfo['name'] ?? null,
            'picture' => $userInfo['picture'] ?? null,
        ];
    }
}
