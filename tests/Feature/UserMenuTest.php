<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class UserMenuTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authorized_user_menu_returns_rc_0(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_Menu', ['tlyle', 1])
            ->andReturn([
                'rc' => 0,
                'rows' => []
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_Menu',
            'params' => ['tlyle', 1]
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'data' => [],
                     'error' => null
                 ]);

        @unlink($cacheFile);
    }

    public function test_unauthorized_user_menu_returns_rc_99(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_Menu', ['tlyle', 999])
            ->andReturn([
                'rc' => 99,
                'rows' => []
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_Menu',
            'params' => ['tlyle', 999]
        ]);

        // ARCHITECTURE.md: Business failure (non-zero rc) returns 422
        $response->assertStatus(422)
                 ->assertJson([
                     'rc' => 99,
                     'ok' => false,
                     'data' => [],
                     'error' => ['code' => 99]
                 ]);

        @unlink($cacheFile);
    }
}
