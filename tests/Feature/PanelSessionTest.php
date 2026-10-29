<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Endpoints\Concerns\CreatesApiSchema;
use Tests\TestCase;

class PanelSessionTest extends TestCase
{
    use CreatesApiSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createApiSchema();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function panelRoles(): array
    {
        return [
            'admin' => ['admin'],
            'teknisi' => ['teknisi'],
        ];
    }

    #[DataProvider('panelRoles')]
    public function test_panel_session_stores_role_from_valid_token(string $role): void
    {
        Http::fake();

        $user = $this->makeUser($role);
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->postJson('/session/panel', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('role', $role)
            ->assertCookie('pandansari_panel');

        Http::assertNothingSent();
    }

    public function test_panel_session_rejects_customer_token(): void
    {
        $user = $this->makeUser('pelanggan');
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->postJson('/session/panel', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('role', 'pelanggan');
    }

    public function test_panel_session_rejects_invalid_and_expired_tokens(): void
    {
        $this->postJson('/session/panel', ['token' => 'token-palsu'])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                'Token tidak valid atau sesi backend sudah berakhir. Silakan login ulang.'
            );

        $user = $this->makeUser('admin');
        $token = $user->createToken('auth_token')->plainTextToken;

        $user->tokens()->first()->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/session/panel', ['token' => $token])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    private function makeUser(string $role): User
    {
        return User::create([
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => ucfirst($role).' Panel',
            'email' => $role.'_'.Str::lower(Str::random(8)).'@example.com',
            'nomor_telepon' => null,
            'kata_sandi' => 'Rahasia1@',
            'peran' => $role,
        ]);
    }
}
