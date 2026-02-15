<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class UserMenuSaveTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_user_menu_save_success(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_Menu_Save', ['tlyle', 'mnuMainDashboard', 1])
            ->andReturn([
                'rc' => 0,
                'rows' => []
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        // Mock tenant allowlist
        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_Menu_Save',
            'params' => ['tlyle', 'mnuMainDashboard', 1]
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

    public function test_user_menu_save_business_failure(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_Menu_Save', ['tlyle', 'invalidKey', 0])
            ->andReturn([
                'rc' => 50,
                'rows' => []
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        // Mock tenant allowlist
        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_Menu_Save',
            'params' => ['tlyle', 'invalidKey', 0]
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'rc' => 50,
                     'ok' => false,
                     'error' => ['code' => 50]
                 ]);

        @unlink($cacheFile);
    }

    public function test_user_menu_save_rejects_invalid_params(): void
    {
        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_Menu_Save',
            'params' => ['tlyle', 'mnuMainDashboard'] // Missing 'Allowed'
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'rc' => 99,
                     'ok' => false,
                     'error' => 'Invalid request.'
                 ]);
    }
}
