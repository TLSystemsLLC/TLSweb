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

        $mockRegistry = ['test' => true];
        $this->mockClient = Mockery::mock(StoredProcedureClient::class);
        $this->app->instance(StoredProcedureClient::class, $this->mockClient);

        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn " . var_export($mockRegistry, true) . ";\n");
    }

    protected function tearDown(): void
    {
        $cacheFile = storage_path('framework/cache/tenants.php');
        @unlink($cacheFile);
        Mockery::close();
        parent::tearDown();
    }

    public function test_failed_login_logs_correctly_when_rc_is_non_zero(): void
    {
        $this->mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->andReturn([
                'rc' => 99,
                'rows' => []
            ]);

        Log::shouldReceive('warning')
            ->with('SP business failure', Mockery::on(fn($context) => $context['rc'] === 99))
            ->once();

        $response = $this->postJson('/api/sp', [
            'login' => 'test.user',
            'proc' => 'spUser_Login',
            'params' => ['user', 'wrong_pass']
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('rc', 99);
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
