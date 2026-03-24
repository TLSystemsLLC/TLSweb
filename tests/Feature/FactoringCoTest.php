<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use App\Support\TenantRegistry;
use Tests\TestCase;

class FactoringCoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TenantRegistry::clearTestCache();

        $cacheFile = storage_path('framework/cache/tenants.php');
        if (!is_dir(dirname($cacheFile))) {
            @mkdir(dirname($cacheFile), 0775, true);
        }
        file_put_contents($cacheFile, "<?php\nreturn ['test' => true];\n");

        // Mock getTenants for the TenantRegistry
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->with('getTenants', [])
            ->andReturn([
                'rc' => 0,
                'rows' => [
                    ['tenant_id' => 'test']
                ]
            ]);
        $this->app->instance(StoredProcedureClient::class, $mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        @unlink(storage_path('framework/cache/tenants.php'));
        parent::tearDown();
    }

    public function test_api_can_search_factoring_cos(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->with('getTenants', [])
            ->andReturn(['rc' => 0, 'rows' => [['tenant_id' => 'test']]]);

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->andReturn([
                'rc' => 0,
                'rows' => [
                    ['ID' => 1, 'Name' => 'Factoring Co A'],
                    ['ID' => 2, 'Name' => 'Factoring Co B'],
                ]
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $response = $this->postJson('/api/sp', [
            'login' => 'test.user',
            'proc' => 'spFactoringCo_Search',
            'params' => ['', 100]
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'data' => [
                         ['ID' => 1, 'Name' => 'Factoring Co A'],
                         ['ID' => 2, 'Name' => 'Factoring Co B'],
                     ]
                 ]);
    }

    public function test_api_can_get_single_factoring_co(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->with('getTenants', [])
            ->andReturn(['rc' => 0, 'rows' => [['tenant_id' => 'test']]]);

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->andReturn([
                'rc' => 0,
                'rows' => [
                    ['ID' => 1, 'Name' => 'Factoring Co A', 'Email' => 'a@example.com']
                ]
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $response = $this->postJson('/api/sp', [
            'login' => 'test.user',
            'proc' => 'spFactoringCo_Get',
            'params' => [1]
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'data' => [
                         ['ID' => 1, 'Name' => 'Factoring Co A']
                     ]
                 ]);
    }

    public function test_api_can_save_factoring_co(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->with('getTenants', [])
            ->andReturn(['rc' => 0, 'rows' => [['tenant_id' => 'test']]]);

        $params = [0, 'New Co', '123 St', 'City', 'ST', '12345', '123456789', '987654321', 'new@example.com'];

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->andReturn(['rc' => 0, 'rows' => []]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $response = $this->postJson('/api/sp', [
            'login' => 'test.user',
            'proc' => 'spFactoringCo_Save',
            'params' => $params
        ]);

        $response->assertStatus(200)
                 ->assertJson(['rc' => 0, 'ok' => true]);
    }

    public function test_api_converts_null_to_empty_string_for_string_params(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->with('getTenants', [])
            ->andReturn(['rc' => 0, 'rows' => [['tenant_id' => 'test']]]);

        // Mock with nulls for optional fields
        $inputParams = [0, 'New Co', null, null, null, null, null, null, null];
        // Expected params to have empty strings instead of nulls
        $expectedParams = [0, 'New Co', '', '', '', '', '', '', ''];

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('test', 'spFactoringCo_Save', $expectedParams)
            ->andReturn(['rc' => 0, 'rows' => []]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $response = $this->postJson('/api/sp', [
            'login' => 'test.user',
            'proc' => 'spFactoringCo_Save',
            'params' => $inputParams
        ]);

        $response->assertStatus(200)
                 ->assertJson(['rc' => 0, 'ok' => true]);
    }

    public function test_api_can_delete_factoring_co(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->with('getTenants', [])
            ->andReturn(['rc' => 0, 'rows' => [['tenant_id' => 'test']]]);

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->andReturn(['rc' => 0, 'rows' => []]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $response = $this->postJson('/api/sp', [
            'login' => 'test.user',
            'proc' => 'spFactoringCo_Delete',
            'params' => [1]
        ]);

        $response->assertStatus(200)
                 ->assertJson(['rc' => 0, 'ok' => true]);
    }
}
