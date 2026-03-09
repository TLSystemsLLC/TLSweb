<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class LoginTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_login_success(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $this->app->instance(StoredProcedureClient::class, $mockClient);

        \App\Support\TenantRegistry::clearTestCache();

        // Mock getTenants to return mrwr
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->with('getTenants', [])
            ->andReturn([
                'rc' => 0,
                'rows' => [['tenant_id' => 'mrwr']]
            ]);

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_Login', ['tlyle', 'secret'])
            ->andReturn([
                'rc' => 0,
                'rows' => []
            ]);

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_Login',
            'params' => ['tlyle', 'secret']
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'data' => [],
                     'error' => null
                 ]);
    }

    public function test_login_failure(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $this->app->instance(StoredProcedureClient::class, $mockClient);

        \App\Support\TenantRegistry::clearTestCache();

        // Mock getTenants to return mrwr
        $mockClient->shouldReceive('execMasterWithReturnCode')
            ->with('getTenants', [])
            ->andReturn([
                'rc' => 0,
                'rows' => [['tenant_id' => 'mrwr']]
            ]);

        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_Login', ['tlyle', 'wrong'])
            ->andReturn([
                'rc' => 100,
                'rows' => []
            ]);

        $response = $this->postJson('/api/sp', [
            'login' => 'mrwr.tlyle',
            'proc' => 'spUser_Login',
            'params' => ['tlyle', 'wrong']
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'rc' => 100,
                     'ok' => false,
                     'error' => ['code' => 100]
                 ]);
    }
}
