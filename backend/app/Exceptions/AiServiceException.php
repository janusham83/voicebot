<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class AiServiceException extends Exception
{
    /**
     * A user-friendly message that can be shown in the UI.
     */
    public string $userMessage;

    /**
     * @param  string  $userMessage  A user-friendly message.
     * @param  string  $developerMessage  A developer-focused message for logging.
     */
    public function __construct(string $userMessage, string $developerMessage = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->userMessage = $userMessage;

        $message = $developerMessage ?: $userMessage;

        parent::__construct($message, $code, $previous);
    }
}