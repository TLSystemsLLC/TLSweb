<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class FactoringCoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock TenantRegistry to allow 'test'
        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['test' => true];\n");
    }

    protected function tearDown(): void
    {
        Mockery::close();
        @unlink(storage_path('framework/cache/tenants.php'));
        parent::tearDown();
    }

    public function test_api_can_get_all_factoring_cos(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('test', 'spFactoringCo_GetAll', [])
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
            'proc' => 'spFactoringCo_GetAll',
            'params' => []
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
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('test', 'spFactoringCo_Get', [1])
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
        $params = [0, 'New Co', '123 St', 'City', 'ST', '12345', '123456789', '987654321', 'new@example.com'];

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('test', 'spFactoringCo_Save', $params)
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

    public function test_api_can_delete_factoring_co(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('test', 'spFactoringCo_Delete', [1])
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
