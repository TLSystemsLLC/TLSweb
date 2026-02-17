<?php

namespace Tests\Feature;

use App\Database\StoredProcedureGateway;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    public function test_contact_form_submission_success(): void
    {
        $this->mock(StoredProcedureGateway::class, function ($mock) {
            $mock->shouldReceive('call')
                ->with(null, 'spContactRequest_Save', [
                    'John Doe',
                    'john@example.com',
                    '555-0100',
                    'Hello world'
                ])
                ->once()
                ->andReturn(['rc' => 0, 'rows' => []]);
        });

        $response = $this->postJson('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '555-0100',
            'message' => 'Hello world'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'rc' => 0,
                     'ok' => true,
                     'message' => 'Thank you! Your message has been sent.'
                 ]);
    }

    public function test_contact_form_honeypot_rejection(): void
    {
        // Gateway should NOT be called if honeypot is filled
        $this->mock(StoredProcedureGateway::class, function ($mock) {
            $mock->shouldNotReceive('call');
        });

        $response = $this->postJson('/contact', [
            'name' => 'Bot',
            'email' => 'bot@bot.com',
            'website' => 'http://spam.com',
            'message' => 'I am a bot'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['rc' => 0, 'ok' => true]);
    }

    public function test_contact_form_validation_failure(): void
    {
        $response = $this->postJson('/contact', [
            'name' => '', // Required
            'email' => 'not-an-email',
            'message' => ''
        ]);

        $response->assertStatus(422);
    }
}
