<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Exceptions;

class AiResponseParseException extends AiGenerationException
{
    public function __construct(string $message = 'Failed to parse AI response', ?\Throwable $previous = null)
    {
        parent::__construct(
            message: $message,
            userMessage: 'Failed to parse AI response. Please try again.',
            previous: $previous,
        );
    }
}
