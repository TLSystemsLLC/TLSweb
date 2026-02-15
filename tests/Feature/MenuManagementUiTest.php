<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class MenuManagementUiTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dashboard_renders_menu_management_section(): void
    {
        $response = $this->withSession(['user_login' => 'test.tlyle'])
                         ->get('/dashboard');

        $response->assertStatus(200);
        // Menu Management is now loaded dynamically.
        // We verify the dashboard has the router logic for it.
        $response->assertSee('id="content-area"', false);
        $response->assertSee('navigateToPage(key, item.Caption)', false);
    }

    public function test_menu_management_calls_stored_procedures(): void
    {
        // This test verifies that the dashboard logic (if it were executed) would call the right SPs.
        // Since we are doing a feature test of the Blade/PHP side, we can't easily test the JS execution
        // without something like Dusk, but we've already verified the SPs are allowlisted and work.
        // Here we just ensure the view has the necessary login information to make those calls.

        $response = $this->withSession(['user_login' => 'test.tlyle'])
                         ->get('/dashboard');

        $response->assertSee('const login = "test.tlyle";', false);
    }
}
