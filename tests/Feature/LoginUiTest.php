<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Mockery;
use Tests\TestCase;

class LoginUiTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('TLS Login');
    }

    public function test_ui_login_success(): void
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

        $response = $this->post('/login', [
            'login' => 'mrwr.tlyle',
            'password' => 'secret'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['rc' => 0, 'ok' => true]);

        $this->assertEquals('mrwr.tlyle', session('user_login'));

        @unlink($cacheFile);
    }

    public function test_ui_login_failure(): void
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

        $response = $this->post('/login', [
            'login' => 'mrwr.tlyle',
            'password' => 'wrong'
        ], ['Accept' => 'application/json']);

        $response->assertStatus(401);
        $response->assertJson(['ok' => false]);

        $this->assertFalse(session()->has('user_login'));

        @unlink($cacheFile);
    }

    public function test_dashboard_requires_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_dashboard_accessible_when_logged_in(): void
    {
        $response = $this->withSession(['user_login' => 'mrwr.tlyle'])
                         ->get('/dashboard');

        $response->assertStatus(200);
        // "Welcome to TLS" is now loaded dynamically, so we check for the container
        $response->assertSee('id="content-area"', false);
    }

    public function test_logout_works(): void
    {
        $response = $this->withSession(['user_login' => 'mrwr.tlyle'])
                         ->post('/logout');

        $response->assertRedirect('/login');
        $this->assertFalse(session()->has('user_login'));
    }
}
