<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Traits;

use Gwhthompson\FilamentAiForms\Agents\ChatStreamAgent;
use Gwhthompson\FilamentAiForms\Agents\FormGenerationAgent;

trait MocksAgent
{
    /**
     * Mock FormGenerationAgent with a successful structured response.
     *
     * @param  array<string, mixed>  $data
     */
    protected function mockFormGenerationSuccess(array $data): void
    {
        FormGenerationAgent::fake([$data])->preventStrayPrompts();
    }

    /**
     * Mock ChatStreamAgent with a successful streaming response.
     */
    protected function mockChatStreamSuccess(string $content = 'Test response'): void
    {
        ChatStreamAgent::fake([$content])->preventStrayPrompts();
    }

    /**
     * Mock FormGenerationAgent to throw an exception.
     */
    protected function mockFormGenerationException(string $message = 'AI API error'): void
    {
        FormGenerationAgent::fake(fn () => throw new \RuntimeException($message))->preventStrayPrompts();
    }

    /**
     * Mock ChatStreamAgent to throw an exception.
     */
    protected function mockChatStreamException(string $message = 'AI API error'): void
    {
        ChatStreamAgent::fake(fn () => throw new \RuntimeException($message))->preventStrayPrompts();
    }
}
