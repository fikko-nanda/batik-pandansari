<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Endpoints\Concerns\CreatesApiSchema;
use Tests\TestCase;

class AccountValidationTest extends TestCase
{
    use CreatesApiSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createApiSchema();
        Mail::fake();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'nama_pengguna' => 'Budi Santoso',
            'email' => 'budi_'.Str::lower(Str::random(8)).'@example.com',
            'nomor_telepon' => '081234567890',
            'kata_sandi' => 'Rahasia123#',
            'kata_sandi_confirmation' => 'Rahasia123#',
        ], $overrides);
    }

    private function makeUser(string $plain = 'Rahasia1@', string $nomorTelepon = '081234567890'): User
    {
        return User::create([
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => 'Pengguna Uji',
            'email' => 'pengguna_'.Str::lower(Str::random(8)).'@example.com',
            'nomor_telepon' => $nomorTelepon,
            'kata_sandi' => $plain,
            'peran' => 'pelanggan',
        ]);
    }

    private function loginToken(User $user, string $plain = 'Rahasia1@'): string
    {
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'kata_sandi' => $plain,
        ]);

        $response->assertOk();

        return (string) $response->json('data.token');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidWhatsAppNumbers(): array
    {
        return [
            '10 digit' => ['0812345678'],
            '14 digit' => ['08123456789012'],
            'mengandung huruf' => ['0812abc78901'],
            'mengandung spasi' => ['0812 3456789'],
            'mengandung tanda plus' => ['+62812345678'],
            'mengandung tanda hubung' => ['0812-3456789'],
            'mengandung simbol' => ['0812#3456789'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validWhatsAppNumbers(): array
    {
        return [
            '11 digit' => ['08123456789'],
            '12 digit' => ['081234567890'],
            '13 digit' => ['6281234567890'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidPasswords(): array
    {
        return [
            'kurang dari 8 karakter' => ['Ab1@xyz', 'minimal 8 karakter'],
            'tanpa huruf besar' => ['ab1@efgh', 'huruf besar'],
            'tanpa huruf kecil' => ['AB1@EFGH', 'huruf kecil'],
            'tanpa angka' => ['Abcdefg@', 'minimal 1 angka'],
            'tanpa simbol' => ['Abcdefg1', 'simbol dari @, #, $, atau %'],
            'simbol di luar daftar' => ['Abcdefg1!', 'simbol dari @, #, $, atau %'],
        ];
    }

    #[DataProvider('invalidWhatsAppNumbers')]
    public function test_registration_rejects_invalid_whatsapp_number(string $number): void
    {
        $response = $this->postJson('/api/register', $this->registrationPayload([
            'nomor_telepon' => $number,
        ]));

        $response->assertUnprocessable()->assertJsonValidationErrors('nomor_telepon');
        $this->assertStringContainsString(
            'Nomor WhatsApp',
            implode(' ', (array) $response->json('errors.nomor_telepon'))
        );
    }

    #[DataProvider('validWhatsAppNumbers')]
    public function test_registration_accepts_11_to_13_digit_whatsapp_number(string $number): void
    {
        $payload = $this->registrationPayload(['nomor_telepon' => $number]);

        $response = $this->postJson('/api/register', $payload);

        $response->assertCreated()->assertJsonPath('data.nomor_telepon', $number);
        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
            'nomor_telepon' => $number,
            'nomor_telepon_normalized' => User::normalizeWhatsAppNumber($number),
        ]);
    }

    public function test_registration_rejects_duplicate_whatsapp_number_in_another_format(): void
    {
        $this->postJson('/api/register', $this->registrationPayload([
            'nomor_telepon' => '081234567891',
        ]))->assertCreated();

        $response = $this->postJson('/api/register', $this->registrationPayload([
            'nomor_telepon' => '6281234567891',
        ]));

        $response->assertUnprocessable()->assertJsonValidationErrors('nomor_telepon');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_database_rejects_duplicate_normalized_whatsapp_number(): void
    {
        $attributes = [
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => 'Pengguna Pertama',
            'email' => 'pertama_'.Str::lower(Str::random(8)).'@example.com',
            'nomor_telepon' => '081234567893',
            'nomor_telepon_normalized' => '6281234567893',
            'kata_sandi' => 'Rahasia1@',
            'peran' => 'pelanggan',
        ];

        User::create($attributes);

        $this->expectException(UniqueConstraintViolationException::class);

        User::create([
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => 'Pengguna Kedua',
            'email' => 'kedua_'.Str::lower(Str::random(8)).'@example.com',
            'nomor_telepon' => '6281234567893',
            'nomor_telepon_normalized' => '6281234567893',
            'kata_sandi' => 'Rahasia1@',
            'peran' => 'pelanggan',
        ]);
    }

    #[DataProvider('invalidPasswords')]
    public function test_registration_rejects_password_that_breaks_policy(string $password, string $expectedMessage): void
    {
        $response = $this->postJson('/api/register', $this->registrationPayload([
            'kata_sandi' => $password,
            'kata_sandi_confirmation' => $password,
        ]));

        $response->assertUnprocessable()->assertJsonValidationErrors('kata_sandi');
        $this->assertStringContainsString(
            $expectedMessage,
            implode(' ', (array) $response->json('errors.kata_sandi'))
        );
    }

    public function test_registration_accepts_password_that_meets_policy(): void
    {
        $payload = $this->registrationPayload([
            'kata_sandi' => 'Rahasia1@',
            'kata_sandi_confirmation' => 'Rahasia1@',
        ]);

        $this->postJson('/api/register', $payload)->assertCreated();

        $this->assertTrue(
            Hash::check('Rahasia1@', User::where('email', $payload['email'])->value('kata_sandi')),
            'Kata sandi baru harus tersimpan ter-hash.'
        );
    }

    public function test_profile_update_rejects_invalid_whatsapp_number(): void
    {
        $user = $this->makeUser();
        $token = $this->loginToken($user);

        $response = $this->withToken($token)->putJson('/api/me', [
            'nomor_telepon' => '0812345678',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('nomor_telepon');
        $this->assertSame('081234567890', $user->fresh()->nomor_telepon);
    }

    public function test_profile_update_accepts_valid_whatsapp_number(): void
    {
        $user = $this->makeUser();
        $token = $this->loginToken($user);

        $this->withToken($token)->putJson('/api/me', [
            'nomor_telepon' => '6281234567890',
        ])->assertOk();

        $this->assertSame('6281234567890', $user->fresh()->nomor_telepon);
        $this->assertSame('6281234567890', $user->fresh()->nomor_telepon_normalized);
    }

    public function test_profile_update_rejects_whatsapp_number_owned_by_another_user(): void
    {
        $user = $this->makeUser(nomorTelepon: '081298765432');
        $this->makeUser(nomorTelepon: '6281298765432');
        $token = $this->loginToken($user);

        $response = $this->withToken($token)->putJson('/api/me', [
            'nomor_telepon' => '6281298765432',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('nomor_telepon');
        $this->assertSame('081298765432', $user->fresh()->nomor_telepon);
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = $this->makeUser();
        $token = $this->loginToken($user);

        $response = $this->withToken($token)->putJson('/api/me/password', [
            'kata_sandi_saat_ini' => 'SalahSekali1@',
            'kata_sandi' => 'SandiBaru1#',
            'kata_sandi_confirmation' => 'SandiBaru1#',
        ]);

        $response->assertUnprocessable()->assertJsonPath('message', 'Kata sandi saat ini salah.');
        $this->assertTrue(Hash::check('Rahasia1@', $user->fresh()->kata_sandi));
    }

    public function test_password_change_rejects_new_password_that_equals_current_password(): void
    {
        $user = $this->makeUser();
        $token = $this->loginToken($user);

        $response = $this->withToken($token)->putJson('/api/me/password', [
            'kata_sandi_saat_ini' => 'Rahasia1@',
            'kata_sandi' => 'Rahasia1@',
            'kata_sandi_confirmation' => 'Rahasia1@',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini.');
        $this->assertTrue(Hash::check('Rahasia1@', $user->fresh()->kata_sandi));
    }

    public function test_password_change_rejects_new_password_that_breaks_policy(): void
    {
        $user = $this->makeUser();
        $token = $this->loginToken($user);

        $response = $this->withToken($token)->putJson('/api/me/password', [
            'kata_sandi_saat_ini' => 'Rahasia1@',
            'kata_sandi' => 'tanpahurufbesar1@',
            'kata_sandi_confirmation' => 'tanpahurufbesar1@',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('kata_sandi');
        $this->assertTrue(Hash::check('Rahasia1@', $user->fresh()->kata_sandi));
    }

    public function test_password_change_stores_new_password_and_invalidates_old_one(): void
    {
        $user = $this->makeUser();
        $token = $this->loginToken($user);

        $this->withToken($token)->putJson('/api/me/password', [
            'kata_sandi_saat_ini' => 'Rahasia1@',
            'kata_sandi' => 'SandiBaru1#',
            'kata_sandi_confirmation' => 'SandiBaru1#',
        ])->assertOk();

        $this->assertTrue(Hash::check('SandiBaru1#', $user->fresh()->kata_sandi));
        $this->postJson('/api/login', [
            'email' => $user->email,
            'kata_sandi' => 'Rahasia1@',
        ])->assertUnauthorized();
        $this->postJson('/api/login', [
            'email' => $user->email,
            'kata_sandi' => 'SandiBaru1#',
        ])->assertOk();
    }

    public function test_otp_password_reset_requires_policy_compliant_password_without_current_password(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

        $otp = (string) Mail::queued(PasswordResetOtpMail::class)->last()->otp;
        $verify = $this->postJson('/api/forgot-password/verify-otp', [
            'email' => $user->email,
            'kode_otp' => $otp,
        ])->assertOk();

        $resetToken = (string) $verify->json('data.reset_token');
        $this->assertNotSame('', $resetToken);

        $weak = $this->postJson('/api/forgot-password/reset', [
            'email' => $user->email,
            'reset_token' => $resetToken,
            'kata_sandi' => 'tanpahurufbesar1@',
            'kata_sandi_confirmation' => 'tanpahurufbesar1@',
        ]);

        $weak->assertUnprocessable()->assertJsonValidationErrors('kata_sandi');
        $this->assertTrue(Hash::check('Rahasia1@', $user->fresh()->kata_sandi));

        $this->postJson('/api/forgot-password/reset', [
            'email' => $user->email,
            'reset_token' => $resetToken,
            'kata_sandi' => 'SandiBaru1#',
            'kata_sandi_confirmation' => 'SandiBaru1#',
        ])->assertOk();

        $this->assertTrue(Hash::check('SandiBaru1#', $user->fresh()->kata_sandi));
    }
}
