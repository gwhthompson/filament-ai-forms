<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Services;

use OpenAI\Responses\Responses\CreateStreamedResponse;
use OpenAI\Responses\StreamResponse;

/**
 * Unified streaming response handler for OpenAI Responses API.
 *
 * Handles:
 * - Stream iteration and delta accumulation
 * - Output buffer flushing for real-time delivery
 * - Livewire wire:stream integration
 * - Callback execution for progress updates
 */
class StreamingResponseHandler
{
    /**
     * Handle streaming response with callbacks.
     *
     * @param  StreamResponse<CreateStreamedResponse>|iterable<int, object>  $stream  OpenAI streaming response or iterable
     * @param  (callable(string, string): void)|null  $onDelta  Called for each delta: fn(string $delta, string $accumulated)
     * @param  (callable(string): void)|null  $onComplete  Called when done: fn(string $fullContent)
     */
    public function handle(
        StreamResponse|iterable $stream,
        ?callable $onDelta = null,
        ?callable $onComplete = null
    ): string {
        // Extend execution time for streaming (OpenAI responses can take > 30s)
        set_time_limit(300);

        $fullContent = '';

        /** @var object $response */
        foreach ($stream as $response) {
            // Handle progressive delta events
            $event = property_exists($response, 'event') ? $response->event : null;

            if ($event === 'response.output_text.delta') {
                $delta = '';
                if (property_exists($response, 'response') && is_object($response->response) && property_exists($response->response, 'delta')) {
                    $rawDelta = $response->response->delta;
                    $delta = is_string($rawDelta) ? $rawDelta : (is_scalar($rawDelta) ? (string) $rawDelta : '');
                }

                if ($delta !== '') {
                    $fullContent .= $delta;

                    if ($onDelta !== null) {
                        $onDelta($delta, $fullContent);
                    }

                    $this->flush();
                }
            }

            // Handle completion event (contains full text)
            if ($event === 'response.output_text.done') {
                if (property_exists($response, 'text')) {
                    $rawText = $response->text;
                    $fullContent = is_string($rawText) ? $rawText : (is_scalar($rawText) ? (string) $rawText : $fullContent);
                }
            }
        }

        if ($onComplete !== null) {
            $onComplete($fullContent);
        }

        return $fullContent;
    }

    /** Flush output buffers for real-time streaming. */
    protected function flush(): void
    {
        if (function_exists('ob_flush')) {
            @ob_flush();
        }

        if (function_exists('flush')) {
            @flush();
        }
    }
}
