<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when an OpenAI API call fails; carries a safe, user-friendly message.
 */
class OpenAIException extends Exception
{
    public string $userMessage;

    public function __construct(string $userMessage, ?string $technicalMessage = null, int $code = 0)
    {
        parent::__construct($technicalMessage ?? $userMessage, $code);

        $this->userMessage = $userMessage;
    }
}
