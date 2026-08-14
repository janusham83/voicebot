<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_and_update_voice_settings(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('data.settings.language', 'auto');

        $this->putJson('/api/settings', [
            'language' => 'si',
            'voice' => 'nova',
            'auto_play' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.settings.language', 'si')
            ->assertJsonPath('data.settings.voice', 'nova')
            ->assertJsonPath('data.settings.auto_play', false);
    }
}
