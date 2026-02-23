<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use App\Database\StoredProcedureGateway;
use App\Support\TenantRegistry;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class LoginLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock TenantRegistry to allow 'test' tenant
        $mockRegistry = [
            'test' => true
        ];

        // Use a simple mock for the gateway's call to avoid full DB connection
        $this->mockClient = Mockery::mock(StoredProcedureClient::class);
        $this->app->instance(StoredProcedureClient::class, $this->mockClient);

        // Mock the file cache for tenants to avoid real master call
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

    public function test_failed_login_logs_correctly_when_rc_is_zero_but_no_rows(): void
    {
        Log::shouldReceive('notice')->with('Incoming SP request', Mockery::any())->once();
        Log::shouldReceive('notice')->with('Login attempt received', Mockery::any())->once();

        $this->mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->andReturn([
                'rc' => 0,
                'rows' => [] // Simulate failed login with RC 0
            ]);

        Log::shouldReceive('notice')->with('SP result received', Mockery::subset(['rc' => 0]))->once();
        Log::shouldReceive('info')->with('SP success', Mockery::any())->once();
        Log::shouldReceive('warning')->with('LOGIN FAILURE: Correct credentials format but no user record found', Mockery::any())->once();

        $response = $this->postJson('/api/sp', [
            'login' => 'test.user',
            'proc' => 'spUser_Login',
            'params' => ['user', 'wrong_pass']
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('rc', 0);
    }

    public function test_failed_login_logs_correctly_when_rc_is_non_zero(): void
    {
        Log::shouldReceive('notice')->with('Incoming SP request', Mockery::any())->once();
        Log::shouldReceive('notice')->with('Login attempt received', Mockery::any())->once();

        $this->mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->andReturn([
                'rc' => 1,
                'rows' => []
            ]);

        Log::shouldReceive('notice')->with('SP result received', Mockery::subset(['rc' => 1]))->once();
        Log::shouldReceive('warning')->with('SP business failure', Mockery::subset(['rc' => 1]))->once();

        $response = $this->postJson('/api/sp', [
            'login' => 'test.user',
            'proc' => 'spUser_Login',
            'params' => ['user', 'wrong_pass']
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('rc', 1);
    }

    public function test_invalid_credentials_format_logs_correctly(): void
    {
        Log::shouldReceive('notice')->with('Incoming SP request', Mockery::any())->once();
        Log::shouldReceive('notice')->with('Login attempt received', Mockery::any())->once();

        // Invalid login format (no dot) triggers InvalidCredentialsException in Tenant::fromLogin
        Log::shouldReceive('warning')->with('Invalid credentials attempt', Mockery::any())->once();

        $response = $this->postJson('/api/sp', [
            'login' => 'malformed_login',
            'proc' => 'spUser_Login',
            'params' => ['user', 'pass']
        ]);

        $response->assertStatus(401);
    }
}
