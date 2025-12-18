<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Services\StreamingResponseHandler;

/**
 * StreamingResponseHandler Tests.
 *
 * Note: OpenAI's StreamResponse class is marked final, so we cannot mock it directly.
 * These tests use a custom iterable wrapper to test the handler's logic without
 * requiring the actual OpenAI SDK streaming infrastructure.
 *
 * For integration testing with real OpenAI responses, see Feature tests.
 */
describe('StreamingResponseHandler', function (): void {
    describe('handle() with MockableStreamResponse', function (): void {
        it('accumulates delta content', function (): void {
            $handler = new StreamingResponseHandler;

            $stream = new MockableStreamResponse([
                createDeltaEvent('Hello'),
                createDeltaEvent(' World'),
                createDeltaEvent('!'),
            ]);

            $result = $handler->handle($stream);

            expect($result)->toBe('Hello World!');
        });

        it('ignores empty deltas', function (): void {
            $handler = new StreamingResponseHandler;

            $stream = new MockableStreamResponse([
                createDeltaEvent('Content'),
                createDeltaEvent(''),
                createDeltaEvent(' More'),
            ]);

            $result = $handler->handle($stream);

            expect($result)->toBe('Content More');
        });

        it('calls onDelta callback for each delta', function (): void {
            $handler = new StreamingResponseHandler;
            $deltas = [];
            $accumulated = [];

            $stream = new MockableStreamResponse([
                createDeltaEvent('A'),
                createDeltaEvent('B'),
                createDeltaEvent('C'),
            ]);

            $handler->handle(
                $stream,
                onDelta: function (string $delta, string $acc) use (&$deltas, &$accumulated): void {
                    $deltas[] = $delta;
                    $accumulated[] = $acc;
                }
            );

            expect($deltas)->toBe(['A', 'B', 'C'])
                ->and($accumulated)->toBe(['A', 'AB', 'ABC']);
        });

        it('calls onComplete callback with full content', function (): void {
            $handler = new StreamingResponseHandler;
            $completedContent = null;

            $stream = new MockableStreamResponse([
                createDeltaEvent('Full '),
                createDeltaEvent('Content'),
            ]);

            $handler->handle(
                $stream,
                onComplete: function (string $content) use (&$completedContent): void {
                    $completedContent = $content;
                }
            );

            expect($completedContent)->toBe('Full Content');
        });

        it('uses done event text when available', function (): void {
            $handler = new StreamingResponseHandler;

            $stream = new MockableStreamResponse([
                createDeltaEvent('Partial'),
                createDoneEvent('Complete Final Text'),
            ]);

            $result = $handler->handle($stream);

            expect($result)->toBe('Complete Final Text');
        });

        it('handles stream with no callbacks', function (): void {
            $handler = new StreamingResponseHandler;

            $stream = new MockableStreamResponse([
                createDeltaEvent('Test'),
            ]);

            $result = $handler->handle($stream);

            expect($result)->toBe('Test');
        });

        it('ignores unrelated events', function (): void {
            $handler = new StreamingResponseHandler;

            $stream = new MockableStreamResponse([
                createEvent('response.created'),
                createDeltaEvent('Only This'),
                createEvent('response.completed'),
            ]);

            $result = $handler->handle($stream);

            expect($result)->toBe('Only This');
        });

        it('returns empty string for stream with no content', function (): void {
            $handler = new StreamingResponseHandler;

            $stream = new MockableStreamResponse([
                createEvent('response.created'),
                createEvent('response.completed'),
            ]);

            $result = $handler->handle($stream);

            expect($result)->toBe('');
        });
    });
});

/**
 * Create a delta event object.
 */
function createDeltaEvent(string $delta): object
{
    $response = new stdClass;
    $response->event = 'response.output_text.delta';
    $response->response = new stdClass;
    $response->response->delta = $delta;

    return $response;
}

/**
 * Create a done event object.
 */
function createDoneEvent(string $text): object
{
    $response = new stdClass;
    $response->event = 'response.output_text.done';
    $response->text = $text;

    return $response;
}

/**
 * Create a generic event object.
 */
function createEvent(string $event): object
{
    $response = new stdClass;
    $response->event = $event;

    return $response;
}

/**
 * A mockable stream response that can be used in place of the final StreamResponse.
 *
 * This class implements IteratorAggregate to allow foreach iteration,
 * matching the StreamResponse interface without requiring the final class.
 *
 * @implements IteratorAggregate<int, object>
 */
class MockableStreamResponse implements IteratorAggregate
{
    /**
     * @param  array<int, object>  $responses
     */
    public function __construct(
        private readonly array $responses
    ) {}

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->responses);
    }
}
