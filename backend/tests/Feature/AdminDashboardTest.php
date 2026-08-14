<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_stats(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'stats' => ['users', 'conversations', 'messages', 'tokens'],
                    'recent_conversations',
                ],
            ]);
    }

    public function test_non_admin_cannot_view_dashboard_stats(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/dashboard')->assertForbidden();
    }
}
