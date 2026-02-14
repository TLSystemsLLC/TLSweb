<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    public function test_api_returns_503_when_database_is_down(): void
    {
        // Force the application to NOT be in testing mode for the client
        // because StoredProcedureClient skips PDO connection in testing mode.
        // Or we can just mock the client to throw ServiceUnavailableHttpException.

        $this->mock(StoredProcedureClient::class, function ($mock) {
            $mock->shouldReceive('__construct')
                 ->andThrow(new \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException(null, 'Database is down.'));
        });

        // Actually, mocking the constructor is hard in PHP.
        // Let's mock a method instead and assume the constructor passed (or was mocked/skipped).

        $mockClient = $this->mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->andThrow(new \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException(null, 'Database is down.'));

        // Mocking the registry to allow the tenant
        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spCompany_Get',
            'params' => [1]
        ]);

        $response->assertStatus(503)
                 ->assertJson([
                     'rc' => 99,
                     'ok' => false,
                     'error' => 'Maintenance.'
                 ]);

        @unlink($cacheFile);
    }

    public function test_web_login_returns_503_when_database_is_down(): void
    {
        // 1. Mock StoredProcedureClient to throw on ANY call
        $mockClient = $this->mock(StoredProcedureClient::class);

        // This handles POST /login
        $mockClient->shouldReceive('execWithReturnCode')
            ->andThrow(new \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException(null, 'Database is down.'));

        // This handles GET /login (via TenantRegistry::allowedTenants)
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->andThrow(new \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException(null, 'Database is down.'));

        // 2. Ensure cache is MISSING or expired so GET /login hits the mock
        $cacheFile = storage_path('framework/cache/tenants.php');
        @unlink($cacheFile);

        // Test POST login (AJAX)
        $response = $this->post('/login', [
            'login' => 'mrwr.tlyle',
            'password' => 'password'
        ], ['Accept' => 'application/json']);

        $response->assertStatus(503)
                 ->assertJson([
                     'rc' => 99,
                     'ok' => false,
                     'error' => 'Maintenance.'
                 ]);

        // Test GET login (Page load)
        $response = $this->get('/login');
        $response->assertStatus(503);
    }

    public function test_artisan_down_returns_503(): void
    {
        // Simulate php artisan down
        $downFile = storage_path('framework/down');
        file_put_contents($downFile, json_encode(['time' => time()]));

        try {
            $response = $this->get('/');
            $response->assertStatus(503);

            $apiResponse = $this->postJson('/api/sp', []);
            $apiResponse->assertStatus(503);
        } finally {
            @unlink($downFile);
        }
    }
}
