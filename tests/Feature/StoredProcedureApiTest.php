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
                     'rc' => 100,
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
                     'rc' => 100,
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
                     'rc' => 100,
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
                     'rc' => 100,
                     'ok' => false,
                     'error' => 'Invalid request.'
                 ]);
    }

    public function test_api_handles_business_failure_rc(): void
    {
        // We need to mock the gateway or client to avoid real DB connection
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        \App\Support\TenantRegistry::clearTestCache();
        $cacheFile = storage_path('framework/cache/tenants.php');
        @unlink($cacheFile);

        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->atLeast()->once()
            ->with('getTenants', [])
            ->andReturn([
                'rc' => 0,
                'rows' => [['tenant_id' => 'MRWR']]
            ]);

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('MRWR', 'spCompany_Get', [1])
            ->andReturn([
                'rc' => 50001,
                'rows' => []
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $response = $this->postJson('/api/sp', [
            'login' => 'MRWR.tlyle',
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
        \App\Support\TenantRegistry::clearTestCache();
        $cacheFile = storage_path('framework/cache/tenants.php');
        @unlink($cacheFile);

        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->atLeast()->once()
            ->with('getTenants', [])
            ->andReturn([
                'rc' => 0,
                'rows' => [['tenant_id' => 'MRWR']]
            ]);

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('MRWR', 'spCompany_Get', [1])
            ->andReturn([
                'rc' => 0,
                'rows' => [['CompanyName' => 'Test Corp']]
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $response = $this->postJson('/api/sp', [
            'login' => 'MRWR.tlyle',
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
