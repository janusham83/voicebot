<?php

namespace Tests\Feature;

use App\Services\GeminiService;
use ReflectionMethod;
use Tests\TestCase;

class GeminiConfigTest extends TestCase
{
    public function test_gemini_service_is_configured(): void
    {
        $this->assertNotNull(config('services.gemini'));
        $this->assertSame('gemini-3.6-flash', config('services.gemini.model'));
        $this->assertSame('https://generativelanguage.googleapis.com/v1beta', config('services.gemini.base_url'));
        $this->assertSame(30, config('services.gemini.timeout'));
    }

    public function test_gemini_service_supports_full_voice_workflow(): void
    {
        $this->assertTrue(method_exists(GeminiService::class, 'generateChatResponse'));
        $this->assertTrue(method_exists(GeminiService::class, 'transcribeAudio'));
        $this->assertTrue(method_exists(GeminiService::class, 'generateSpeech'));
    }

    public function test_gemini_service_normalizes_supported_audio_types(): void
    {
        $this->assertSame('audio/wav', GeminiService::normalizeMimeType('audio/wav'));
        $this->assertSame('audio/webm', GeminiService::normalizeMimeType('audio/webm;codecs=opus'));
        $this->assertSame('audio/webm', GeminiService::normalizeMimeType('video/webm'));
        $this->assertSame('audio/mp3', GeminiService::normalizeMimeType('audio/mpeg'));
        $this->assertSame('audio/mp4', GeminiService::normalizeMimeType('audio/mp4'));
        $this->assertSame('audio/m4a', GeminiService::normalizeMimeType('application/octet-stream', 'audio/x-m4a'));
    }

    public function test_gemini_service_builds_generate_content_url_with_models_prefix(): void
    {
        $service = new GeminiService();
        $method = new ReflectionMethod(GeminiService::class, 'generateContentUrl');
        $method->setAccessible(true);

        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent',
            $method->invoke($service, 'gemini-3.6-flash')
        );

        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent',
            $method->invoke($service, 'models/gemini-3.6-flash')
        );
    }
}
