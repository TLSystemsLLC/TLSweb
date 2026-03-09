<?php

namespace Tests\Feature;

use App\Database\StoredProcedureClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

use App\Support\TenantRegistry;

class CompanyMaintenanceApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // The TenantRegistry now avoids writing to the global cache file during tests.
    }

    public function test_company_search()
    {
        \App\Support\TenantRegistry::clearTestCache();
        $mock = Mockery::mock(StoredProcedureClient::class);
        $this->app->instance(StoredProcedureClient::class, $mock);

        // Mock getTenants to include TLS
        $mock->shouldReceive('execMasterWithReturnCode')
            ->with('getTenants', [])
            ->andReturn([
                'rc' => 0,
                'rows' => [['tenant_id' => 'TLS']]
            ]);

        $mock->shouldReceive('execWithReturnCode')
            ->with('TLS', 'webCompanySearch', ['Test', 1, 100])
            ->once()
            ->andReturn([
                'rc' => 0,
                'rows' => [
                    [
                        'CompanyID' => 1,
                        'CompanyName' => 'Test Company',
                        'ShortName' => 'TESTCO',
                        'MailingCity' => 'Test City',
                        'MailingState' => 'TS',
                        'ShippingCity' => 'Ship City',
                        'ShippingState' => 'SS',
                        'SCAC' => 'TEST',
                        'DUNS' => '123456789',
                        'MC' => 'MC123456',
                        'DOT' => 'DOT123456',
                        'FID' => 'FID123456',
                        'Active' => 1,
                        'TotalRows' => 1,
                        'CurrentPage' => 1,
                        'PageSize' => 100,
                        'TotalPages' => 1,
                        'HasNextPage' => 0
                    ]
                ]
            ]);

        $response = $this->postJson('/api/sp', [
            'login' => 'TLS.user',
            'proc' => 'webCompanySearch',
            'params' => ['Test', 1, 100]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.CompanyName', 'Test Company')
            ->assertJsonPath('data.0.MailingCity', 'Test City');
    }

    public function test_company_get()
    {
        \App\Support\TenantRegistry::clearTestCache();
        $mock = Mockery::mock(StoredProcedureClient::class);
        $this->app->instance(StoredProcedureClient::class, $mock);

        // Mock getTenants to include TLS
        $mock->shouldReceive('execMasterWithReturnCode')
            ->with('getTenants', [])
            ->andReturn([
                'rc' => 0,
                'rows' => [['tenant_id' => 'TLS']]
            ]);

        $mock->shouldReceive('execWithReturnCode')
            ->with('TLS', 'webCompanyGet', [1])
            ->once()
            ->andReturn([
                'rc' => 0,
                'rows' => [
                    [
                        'CompanyID' => 1,
                        'CompanyName' => 'Test Company',
                        'MailingAddress' => '123 Test St',
                        'MailingCity' => 'Test City',
                        'MailingState' => 'TS',
                        'MailingZip' => '12345',
                        'MainPhone' => '123-456-7890',
                        'MainFax' => '098-765-4321',
                        'Active' => 1
                    ]
                ]
            ]);

        $response = $this->postJson('/api/sp', [
            'login' => 'TLS.user',
            'proc' => 'webCompanyGet',
            'params' => [1]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.MailingAddress', '123 Test St')
            ->assertJsonPath('data.0.MainFax', '098-765-4321');
    }

    public function test_company_save()
    {
        \App\Support\TenantRegistry::clearTestCache();
        $mock = Mockery::mock(StoredProcedureClient::class);
        $this->app->instance(StoredProcedureClient::class, $mock);

        // Mock getTenants to include TLS
        $mock->shouldReceive('execMasterWithReturnCode')
            ->with('getTenants', [])
            ->andReturn([
                'rc' => 0,
                'rows' => [['tenant_id' => 'TLS']]
            ]);

        $params = [
            1, // CompanyID
            'Updated Company', // CompanyName
            'SN123', // ShortName
            '456 Updated Ave', // MailingAddress
            'New City', // MailingCity
            'NS', // MailingState
            '54321', // MailingZip
            '789 Ship St', // ShippingAddress
            'Ship City', // ShippingCity
            'SS', // ShippingState
            '67890', // ShippingZip
            '555-0199', // MainPhone
            '555-0188', // MainFax
            'SCAC', // SCAC
            'DUNS', // DUNS
            'ICC', // MC
            'DOT', // DOT
            'FID', // FID
            1, // Active
            1, // FreightDetailPost
            0, // ComdataInterface
            1, // TranfloMobileInterface
            0, // SystemRemitVendor (INT)
            123.45, // APAccount (DECIMAL -> float)
            234.56, // ARAccount
            345.67, // BadDebtAccount
            456.78, // MiscAccount
            567.89, // FreightRevAccount
            678.90, // BrokerRevAccount
            789.01, // FreightPayableAccount
            890.12, // GeneralBankAccount
            901.23, // SettlementBankAccount
            12.34, // SettlementClearingAccount
            23.45, // InterCompanyClearing
            34.56, // InterCompanyAR
            45.67, // InterCompanyAP
            56.78, // FrieghtRevExp
            67.89, // CompanyFreightRevenue
            78.90, // CompanyFreightExpense
            89.01, // CompanyTruckFuelExpense
            90.12, // CompanyReeferFuelExpense
            12.34, // DriverAR
            56.78, // RetainedEarningsAccount
            null // Logo
        ];

        $mock->shouldReceive('execWithReturnCode')
            ->with('TLS', 'webCompanySave', $params)
            ->once()
            ->andReturn([
                'rc' => 0,
                'rows' => []
            ]);

        $response = $this->postJson('/api/sp', [
            'login' => 'TLS.user',
            'proc' => 'webCompanySave',
            'params' => $params
        ]);

        $response->assertStatus(200)
            ->assertJson(['ok' => true]);
    }
}
