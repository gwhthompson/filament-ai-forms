<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

class ChatStreamAgent implements Agent, Conversational
{
    use Promptable;

    /**
     * @param  Message[]  $conversationHistory
     */
    public function __construct(
        private string $systemInstructions = 'You are a helpful AI assistant.',
        private array $conversationHistory = [],
    ) {}

    public function instructions(): string
    {
        return $this->systemInstructions;
    }

    /** @return Message[] */
    public function messages(): iterable
    {
        return $this->conversationHistory;
    }
}
