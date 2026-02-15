<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class UpdateMenuItemTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_api_can_call_update_menu_item_globally(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->once()
            ->with('UpdateMenuItem', ['mnuTest', 1])
            ->andReturn([
                'rc' => 0,
                'rows' => []
            ]);

        $this->app->instance(StoredProcedureClient::class, $mockClient);

        $response = $this->postJson('/api/sp', [
            'proc' => 'UpdateMenuItem',
            'params' => ['mnuTest', 1]
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'data' => [],
                     'error' => null
                 ]);
    }

    public function test_api_rejects_update_menu_item_with_invalid_params(): void
    {
        $response = $this->postJson('/api/sp', [
            'proc' => 'UpdateMenuItem',
            'params' => [123, 'not-a-bit'] // Wrong types
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'rc' => 99,
                     'ok' => false,
                     'error' => 'Invalid request.'
                 ]);
    }
}
