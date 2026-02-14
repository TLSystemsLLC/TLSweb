<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class UserMenusTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_api_can_fetch_user_menus(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_Menus', ['tlyle'])
            ->andReturn([
                'rc' => 0,
                'rows' => [
                    ['MenuID' => 1, 'MenuName' => 'Dashboard', 'Url' => '/dashboard'],
                    ['MenuID' => 2, 'MenuName' => 'Settings', 'Url' => '/settings']
                ]
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        // Mock tenant allowlist
        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_Menus',
            'params' => ['tlyle']
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'data' => [
                         ['MenuID' => 1, 'MenuName' => 'Dashboard', 'Url' => '/dashboard'],
                         ['MenuID' => 2, 'MenuName' => 'Settings', 'Url' => '/settings']
                     ]
                 ]);

        @unlink($cacheFile);
    }

    public function test_fails_if_param_count_mismatch(): void
    {
        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_Menus',
            'params' => [] // Missing UserID
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'rc' => 99,
                     'ok' => false,
                     'error' => 'Invalid request.'
                 ]);
    }
}
