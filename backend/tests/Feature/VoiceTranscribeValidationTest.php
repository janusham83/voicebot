<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoiceTranscribeValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_webm_recording_mime_type_is_allowed(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $file = UploadedFile::fake()->createWithContent(
            'recording.webm',
            "\x1A\x45\xDF\xA3webm"
        );

        $file->mimeType('video/webm');

        $this->postJson('/api/voice/transcribe', [
            'audio' => $file,
            'language' => 'auto',
        ])->assertStatus(422)
            ->assertJsonMissingValidationErrors('audio');
    }
}
