<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class UserMaintenanceApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_api_allows_spUser_Save2(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);

        $params = [
            0,             // Key
            'testuser',    // UserID
            1,             // TeamKey
            'Test User',   // UserName
            'First',       // FirstName
            'Last',        // LastName
            null,          // PasswordChanged
            101,           // Extension
            1,             // SatelliteInstalls
            'password123', // Password
            'test@example.com', // Email
            null,          // LastLogin
            null,          // HireDate
            0,             // RapidLogUser
            1,             // Active
            1,             // UserType
            1,             // CompanyID
            1,             // DivisionID
            1,             // DepartmentID
            '123456',      // Phone
            '654321'       // Fax
        ];

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_Save2', $params)
            ->andReturn([
                'rc' => 0,
                'rows' => [['NewKey' => 123]]
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        // Seed tenant cache
        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_Save2',
            'params' => $params
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'data' => [['NewKey' => 123]],
                     'error' => null
                 ]);

        @unlink($cacheFile);
    }

    public function test_api_allows_webUserSearch(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);

        $params = ['test', 1, 100];

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'webUserSearch', $params)
            ->andReturn([
                'rc' => 0,
                'rows' => [[
                    'UserID' => 'testuser',
                    'UserName' => 'Test User',
                    'TotalRows' => 1,
                    'CurrentPage' => 1,
                    'PageSize' => 100,
                    'TotalPages' => 1,
                    'HasNextPage' => 0
                ]]
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        // Seed tenant cache
        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'webUserSearch',
            'params' => $params
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'data' => [[
                         'UserID' => 'testuser',
                         'UserName' => 'Test User',
                         'TotalRows' => 1,
                         'CurrentPage' => 1,
                         'PageSize' => 100,
                         'TotalPages' => 1,
                         'HasNextPage' => 0
                     ]],
                     'error' => null
                 ]);

        @unlink($cacheFile);
    }
}
