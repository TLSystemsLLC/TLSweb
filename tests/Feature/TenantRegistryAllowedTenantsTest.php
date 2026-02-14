<?php

namespace Tests\Unit;

use App\Database\StoredProcedureClient;
use App\Database\StoredProcedureGateway;
use App\Support\TenantRegistry;
use Mockery;
use Tests\TestCase;

final class TenantRegistryAllowedTenantsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_allowed_tenants_calls_gateway_in_global_scope_and_caches(): void
    {
        $cacheFile = storage_path('framework/cache/tenants.php');
        @unlink($cacheFile);

        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->once()
            ->with('getTenants', [])
            ->andReturn([
                'rc' => 0,
                'rows' => [
                    ['tenant_id' => 'tenant_a'],
                    ['tenant_id' => 'tenant_b'],
                ],
            ]);

        $gateway = new StoredProcedureGateway($mockClient);
        $this->app->instance(StoredProcedureGateway::class, $gateway);

        $map = TenantRegistry::allowedTenants();

        $this->assertTrue($map['tenant_a'] ?? false);
        $this->assertTrue($map['tenant_b'] ?? false);

        // Second call should hit file cache, not gateway
        $map2 = TenantRegistry::allowedTenants();
        $this->assertEquals($map, $map2);

        @unlink($cacheFile);
    }
}
