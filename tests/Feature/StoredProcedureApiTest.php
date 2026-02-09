<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use App\Database\StoredProcedureGateway;
use App\Support\TenantRegistry;
use Mockery;
use Tests\TestCase;

class StoredProcedureApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_api_requires_login_for_tenant_proc(): void
    {
        $response = $this->postJson('/api/sp', [
            'proc' => 'spCompany_Get',
            'params' => [1]
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'rc' => 99,
                     'ok' => false,
                     'error' => 'Invalid credentials.'
                 ]);
    }

    public function test_api_rejects_unlisted_proc(): void
    {
        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUnknown_Proc',
            'params' => []
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'rc' => 99,
                     'ok' => false,
                     'error' => 'Invalid request.'
                 ]);
    }

    public function test_api_rejects_invalid_param_count(): void
    {
        // spCompany_Get expects 1 param
        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spCompany_Get',
            'params' => [1, 2]
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'rc' => 99,
                     'ok' => false,
                     'error' => 'Invalid request.'
                 ]);
    }

    public function test_api_rejects_invalid_param_type(): void
    {
        // spCompany_Get expects int
        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spCompany_Get',
            'params' => ['not-an-int']
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'rc' => 99,
                     'ok' => false,
                     'error' => 'Invalid request.'
                 ]);
    }

    public function test_api_handles_business_failure_rc(): void
    {
        // We need to mock the gateway or client to avoid real DB connection
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spCompany_Get', [1])
            ->andReturn([
                'rc' => 50001,
                'rows' => []
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        // Mock TenantRegistry to allow 'mrwr'
        // Since TenantRegistry uses static methods and file cache, it's harder to mock directly without mocking the filesystem or using a library that can mock static methods.
        // But Tenant::fromLogin calls TenantRegistry::isAllowed.
        // Let's try to seed the cache file.
        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spCompany_Get',
            'params' => [1]
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'rc' => 50001,
                     'ok' => false,
                     'error' => ['code' => 50001]
                 ]);

        @unlink($cacheFile);
    }

    public function test_api_handles_success(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spCompany_Get', [1])
            ->andReturn([
                'rc' => 0,
                'rows' => [['CompanyName' => 'Test Corp']]
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spCompany_Get',
            'params' => [1]
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'data' => [['CompanyName' => 'Test Corp']],
                     'error' => null
                 ]);

        @unlink($cacheFile);
    }
}
