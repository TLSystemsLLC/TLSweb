<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class LoginLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(StoredProcedureClient::class);
        $this->app->instance(StoredProcedureClient::class, $this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_failed_login_logs_correctly_when_rc_is_non_zero(): void
    {
        \App\Support\TenantRegistry::clearTestCache();
        $cacheFile = storage_path('framework/cache/tenants.php');
        @unlink($cacheFile);

        $this->mockClient->shouldReceive('execMasterWithReturnCode')
            ->atLeast()->once()
            ->with('getTenants', [])
            ->andReturn([
                'rc' => 0,
                'rows' => [['tenant_id' => 'MRWR']]
            ]);

        $this->mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('MRWR', 'spUser_Login', ['tlyle', 'wrong_pass'])
            ->andReturn([
                'rc' => 100,
                'rows' => []
            ]);

        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('error')->atMost()->once();
        Log::shouldReceive('notice')->atMost()->once();

        $response = $this->postJson('/api/sp', [
            'login' => 'MRWR.tlyle',
            'proc' => 'spUser_Login',
            'params' => ['tlyle', 'wrong_pass']
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('rc', 100);
    }

    public function test_invalid_credentials_format_logs_correctly(): void
    {
        Log::shouldReceive('warning')
            ->with('Invalid credentials attempt', Mockery::any())
            ->once();

        $response = $this->postJson('/api/sp', [
            'login' => 'malformed_login',
            'proc' => 'spUser_Login',
            'params' => ['user', 'pass']
        ]);

        $response->assertStatus(401);
    }
}
