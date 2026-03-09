<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class GetMenuItemsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_api_can_call_get_menu_items_globally(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->once()
            ->with('GetMenuItems', [0])
            ->andReturn([
                'rc' => 0,
                'rows' => [
                    ['MenuID' => 1, 'Title' => 'Dashboard', 'Active' => 1],
                    ['MenuID' => 2, 'Title' => 'Settings', 'Active' => 1],
                ]
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        // Global calls don't require login
        $response = $this->postJson('/api/sp', [
            'proc' => 'GetMenuItems',
            'params' => [0]
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'data' => [
                         ['MenuID' => 1, 'Title' => 'Dashboard'],
                         ['MenuID' => 2, 'Title' => 'Settings'],
                     ],
                     'error' => null
                 ]);
    }

    public function test_api_rejects_get_menu_items_without_params(): void
    {
        $response = $this->postJson('/api/sp', [
            'proc' => 'GetMenuItems',
            'params' => [] // Missing ShowInactive
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'rc' => 100,
                     'ok' => false,
                     'error' => 'Invalid request.'
                 ]);
    }
}
