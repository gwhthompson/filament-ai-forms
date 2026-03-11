<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Exceptions;

class AiServiceTimeoutException extends AiGenerationException
{
    public function __construct(string $message = 'AI service timeout', ?\Throwable $previous = null)
    {
        parent::__construct(
            message: $message,
            userMessage: 'The AI service took too long to respond. Please try again.',
            previous: $previous,
        );
    }
}
