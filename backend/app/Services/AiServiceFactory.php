<?php

namespace App\Services;

use App\Exceptions\AiServiceException;
use App\Models\User;
use Illuminate\Support\Str;

class AiServiceFactory
{
    public function __construct(
        protected GeminiService $geminiService
    ) {
    }

    /**
     * Get the appropriate AI service based on the user's settings.
     *
     * @throws AiServiceException if no AI services are configured.
     */
    public function make(?User $user): AiServiceInterface
    {
        if ($this->geminiService->isConfigured()) {
            return $this->geminiService;
        }

        // If we reach here, no services are configured.
        throw new AiServiceException('The AI service is not configured correctly. Please contact support.', 'The Gemini API key is not configured in the environment file.');
    }
}