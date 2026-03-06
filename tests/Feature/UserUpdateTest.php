<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_user_update_with_null_dates_fails_due_to_type_cast(): void
    {
        // This test mimics what happens when the JS sends nulls for dates
        // and they are cast to empty strings by StoredProcedureGateway.

        $mockClient = Mockery::mock(StoredProcedureClient::class);

        // Parameters as they would be received from the JS (simplified for the test)
        $paramsFromJs = [
            123,           // Key
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

        // StoredProcedureGateway will now pass null as null for 'string' type
        $expectedParamsToClient = $paramsFromJs;

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_Save2', $expectedParamsToClient)
            ->andReturn([
                'rc' => 0,
                'rows' => []
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        // Seed tenant cache
        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_Save2',
            'params' => $paramsFromJs
        ]);

        $response->assertStatus(200);

        @unlink($cacheFile);
    }
}
