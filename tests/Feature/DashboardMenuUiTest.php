<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class DashboardMenuUiTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dashboard_renders_menu_based_on_permissions(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);

        // 1. Mock GetMenuItems (Global)
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->with('GetMenuItems', [])
            ->andReturn([
                'rc' => 0,
                'rows' => [
                    ['MenuID' => 1, 'MenuKey' => 'mnuMainDashboard', 'Title' => 'Dashboard'],
                    ['MenuID' => 2, 'MenuKey' => 'mnuMainSettings', 'Title' => 'Settings'],
                    ['MenuID' => 3, 'MenuKey' => 'sep1', 'Title' => 'Separator'],
                    ['MenuID' => 4, 'MenuKey' => 'secAdmin', 'Title' => 'Security Only'],
                    ['MenuID' => 5, 'MenuKey' => 'mnuSubProfile', 'Title' => 'Profile', 'ParentMenuID' => 1],
                ]
            ]);

        // 2. Mock spUser_Menus (Tenant)
        $mockClient->shouldReceive('execWithReturnCode')
            ->with('mrwr', 'spUser_Menus', ['tlyle'])
            ->andReturn([
                'rc' => 0,
                'rows' => [
                    ['MenuKey' => 'mnuMainDashboard'],
                    ['MenuKey' => 'mnuSubProfile'],
                    ['MenuKey' => 'secAdmin'], // Should be filtered out by JS
                ]
            ]);

        // 3. Mock other info calls to avoid errors
        $mockClient->shouldReceive('execWithReturnCode')
            ->with('mrwr', 'spCompany_Get', [1])
            ->andReturn(['rc' => 0, 'rows' => []]);

        $mockClient->shouldReceive('execWithReturnCode')
            ->with('mrwr', 'spUser_GetByID', ['tlyle'])
            ->andReturn(['rc' => 0, 'rows' => []]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        // Mock tenant allowlist
        $cacheFile = storage_path('framework/cache/tenants.php');
        @mkdir(dirname($cacheFile), 0775, true);
        file_put_contents($cacheFile, "<?php\nreturn ['mrwr' => true];\n");

        $response = $this->withSession(['user_login' => 'mrwr.tlyle'])
                         ->get('/dashboard');

        $response->assertStatus(200);

        // Since it's client-side rendering, we can only check if the script is present
        // and the container exists.
        $response->assertSee('id="main-menu"', false);
        $response->assertSee('GetMenuItems');
        $response->assertSee('spUser_Menus');

        @unlink($cacheFile);
    }
}
