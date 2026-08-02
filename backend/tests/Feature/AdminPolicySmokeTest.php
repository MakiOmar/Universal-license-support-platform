<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminPolicySmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_admin_user_passes_license_and_ticket_gates(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', License::class));
        $this->assertTrue(Gate::forUser($user)->allows('viewAny', SupportTicket::class));
        $this->assertTrue(Gate::forUser($user)->allows('create', License::class));
        $this->assertTrue(Gate::forUser($user)->allows('create', SupportTicket::class));
    }

    public function test_admin_login_page_and_panel_do_not_fatal(): void
    {
        $login = $this->get('/admin/login');
        $login->assertOk();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Filament may redirect around auth; ensure no 500/TypeError.
        $panel = $this->get('/admin');
        $this->assertNotEquals(500, $panel->status());
        $this->assertStringNotContainsString('must be of type', $panel->getContent());

        $licenses = $this->get('/admin/licenses');
        $this->assertNotEquals(500, $licenses->status());
        $this->assertStringNotContainsString('must be of type', $licenses->getContent());

        $tickets = $this->get('/admin/support-tickets');
        $this->assertNotEquals(500, $tickets->status());
        $this->assertStringNotContainsString('must be of type', $tickets->getContent());
    }
}
