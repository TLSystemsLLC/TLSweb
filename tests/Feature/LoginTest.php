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
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_Login', ['tlyle', 'secret'])
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

        @unlink($cacheFile);
    }

    public function test_login_failure(): void
    {
        $mockClient = Mockery::mock(StoredProcedureClient::class);
        $mockClient->shouldReceive('execWithReturnCode')
            ->once()
            ->with('mrwr', 'spUser_Login', ['tlyle', 'wrong'])
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
            'proc' => 'spUser_Login',
            'params' => ['tlyle', 'wrong']
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'rc' => 99,
                     'ok' => false,
                     'error' => ['code' => 99]
                 ]);

        @unlink($cacheFile);
    }
}
