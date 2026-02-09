<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class UserGetByIdTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_api_can_fetch_user_by_id(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_GetByID', ['tlyle'])
            ->andReturn([
                'rc' => 0,
                'rows' => [[
                    'UserID' => 'tlyle',
                    'UserName' => 'Tony Lyle',
                    'Email' => 'tlyle@example.com'
                ]]
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_GetByID',
            'params' => ['tlyle']
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'data' => [[
                         'UserID' => 'tlyle',
                         'UserName' => 'Tony Lyle',
                         'Email' => 'tlyle@example.com'
                     ]]
                 ]);

        @unlink($cacheFile);
    }
}
