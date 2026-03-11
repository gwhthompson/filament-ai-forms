<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Exceptions;

use RuntimeException;

class AiGenerationException extends RuntimeException
{
    public function __construct(
        string $message,
        private string $userMessage,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }
}
