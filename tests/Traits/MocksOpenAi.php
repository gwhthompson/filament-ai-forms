<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Traits;

use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Testing\Enums\OverrideStrategy;

trait MocksOpenAi
{
    /**
     * Mock the OpenAI client with a successful response.
     *
     * Uses the built-in OpenAI testing utilities.
     *
     * @param  array<string, mixed>  $data  The JSON data to return as outputText
     */
    protected function mockOpenAiSuccess(array $data): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'status' => 'completed',
                'output' => [
                    [
                        'type' => 'message',
                        'id' => 'msg_test_'.uniqid(),
                        'status' => 'completed',
                        'role' => 'assistant',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => json_encode($data, JSON_THROW_ON_ERROR),
                                'annotations' => [],
                            ],
                        ],
                    ],
                ],
            ], strategy: OverrideStrategy::Replace),
        ]);
    }

    /**
     * Mock the OpenAI client with an incomplete response.
     */
    protected function mockOpenAiIncomplete(string $reason = 'max_output_tokens'): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'status' => 'incomplete',
                'incomplete_details' => [
                    'reason' => $reason,
                ],
                'output' => [],
            ], strategy: OverrideStrategy::Replace),
        ]);
    }

    /**
     * Mock the OpenAI client with an incomplete response but no details.
     *
     * Tests the fallback to 'unknown' when incompleteDetails is missing.
     */
    protected function mockOpenAiIncompleteNoDetails(): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'status' => 'incomplete',
                'incomplete_details' => null,
                'output' => [],
            ], strategy: OverrideStrategy::Replace),
        ]);
    }

    /**
     * Mock the OpenAI client with null content (empty output).
     */
    protected function mockOpenAiNullContent(): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'status' => 'completed',
                'output' => [],
            ], strategy: OverrideStrategy::Replace),
        ]);
    }

    /**
     * Mock the OpenAI client with empty content.
     */
    protected function mockOpenAiEmptyContent(): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'status' => 'completed',
                'output' => [
                    [
                        'type' => 'message',
                        'id' => 'msg_test_'.uniqid(),
                        'status' => 'completed',
                        'role' => 'assistant',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => '',
                                'annotations' => [],
                            ],
                        ],
                    ],
                ],
            ], strategy: OverrideStrategy::Replace),
        ]);
    }

    /**
     * Mock the OpenAI client with non-array JSON response.
     *
     * Used to test the case where valid JSON is returned but it's not an object/array.
     */
    protected function mockOpenAiNonArrayJson(string $jsonValue = '"just a string"'): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'status' => 'completed',
                'output' => [
                    [
                        'type' => 'message',
                        'id' => 'msg_test_'.uniqid(),
                        'status' => 'completed',
                        'role' => 'assistant',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => $jsonValue,
                                'annotations' => [],
                            ],
                        ],
                    ],
                ],
            ], strategy: OverrideStrategy::Replace),
        ]);
    }

    /**
     * Mock the OpenAI client to throw an exception.
     */
    protected function mockOpenAiException(string $message = 'OpenAI API error'): void
    {
        OpenAI::fake([
            new \RuntimeException($message),
        ]);
    }

    /**
     * Mock the OpenAI client with multiple sequential responses.
     *
     * Useful for testing retry logic where first responses fail validation.
     *
     * @param  array<array<string, mixed>>  $responses  Array of response data arrays
     */
    protected function mockOpenAiSequence(array $responses): void
    {
        $fakes = [];
        foreach ($responses as $data) {
            $fakes[] = CreateResponse::fake([
                'status' => 'completed',
                'output' => [
                    [
                        'type' => 'message',
                        'id' => 'msg_test_'.uniqid(),
                        'status' => 'completed',
                        'role' => 'assistant',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => json_encode($data, JSON_THROW_ON_ERROR),
                                'annotations' => [],
                            ],
                        ],
                    ],
                ],
            ], strategy: OverrideStrategy::Replace);
        }
        OpenAI::fake($fakes);
    }

    /**
     * Mock the OpenAI client with a streaming response.
     *
     * Creates a StreamResponse with the specified content.
     * Uses a custom fixture since the package's default fixture is missing required fields.
     *
     * @param  string  $content  The final content to stream
     */
    protected function mockOpenAiStreamSuccess(string $content = 'Test response'): void
    {
        // Build custom fixture with specified content
        // (package's default fixture is outdated - missing sequence_number)
        $fixture = $this->buildStreamingFixture($content);
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $fixture);
        rewind($resource);

        OpenAI::fake([
            \OpenAI\Responses\Responses\CreateStreamedResponse::fake($resource),
        ]);
    }

    /**
     * Build SSE fixture string for streaming response.
     *
     * Mirrors the package's fixture format but with custom content.
     */
    protected function buildStreamingFixture(string $content): string
    {
        $id = 'resp_67c9fdcecf488190bdd9a0409de3a1ec07b8b0ad4e5eb654';
        $msgId = 'msg_67c9fdcf37fc8190ba82116e33fb28c507b8b0ad4e5eb654';
        // Properly escape content for JSON embedding (handles newlines, tabs, unicode, etc.)
        $escapedContent = trim(json_encode($content), '"');

        return implode("\n", [
            'data: {"type":"response.created","response":{"id":"'.$id.'","object":"response","created_at":1741290958,"status":"in_progress","error":null,"incomplete_details":null,"instructions":"You are a helpful assistant.","max_output_tokens":null,"model":"gpt-4o-2024-08-06","output":[],"parallel_tool_calls":true,"previous_response_id":null,"reasoning":{"effort":null,"summary":null},"store":true,"temperature":1.0,"text":{"format":{"type":"text"}},"tool_choice":"auto","tools":[],"top_p":1.0,"truncation":"disabled","usage":null,"user":null,"metadata":{}}}',
            'data: {"type":"response.in_progress","response":{"id":"'.$id.'","object":"response","created_at":1741290958,"status":"in_progress","error":null,"incomplete_details":null,"instructions":"You are a helpful assistant.","max_output_tokens":null,"model":"gpt-4o-2024-08-06","output":[],"parallel_tool_calls":true,"previous_response_id":null,"reasoning":{"effort":null,"summary":null},"store":true,"temperature":1.0,"text":{"format":{"type":"text"}},"tool_choice":"auto","tools":[],"top_p":1.0,"truncation":"disabled","usage":null,"user":null,"metadata":{}}}',
            'data: {"type":"response.output_item.added","output_index":0,"item":{"id":"'.$msgId.'","type":"message","status":"in_progress","role":"assistant","content":[]}}',
            'data: {"type":"response.content_part.added","item_id":"'.$msgId.'","output_index":0,"content_index":0,"part":{"type":"output_text","text":"","annotations":[]}}',
            'data: {"type":"response.output_text.delta","item_id":"'.$msgId.'","output_index":0,"content_index":0,"sequence_number":0,"delta":"'.$escapedContent.'"}',
            'data: {"type":"response.output_text.done","item_id":"'.$msgId.'","output_index":0,"content_index":0,"sequence_number":1,"text":"'.$escapedContent.'"}',
            'data: {"type":"response.content_part.done","item_id":"'.$msgId.'","output_index":0,"content_index":0,"part":{"type":"output_text","text":"'.$escapedContent.'","annotations":[]}}',
            'data: {"type":"response.output_item.done","output_index":0,"item":{"id":"'.$msgId.'","type":"message","status":"completed","role":"assistant","content":[{"type":"output_text","text":"'.$escapedContent.'","annotations":[]}]}}',
            'data: {"type":"response.completed","response":{"id":"'.$id.'","object":"response","created_at":1741290958,"status":"completed","error":null,"incomplete_details":null,"instructions":"You are a helpful assistant.","max_output_tokens":null,"model":"gpt-4o-2024-08-06","output":[{"id":"'.$msgId.'","type":"message","status":"completed","role":"assistant","content":[{"type":"output_text","text":"'.$escapedContent.'","annotations":[]}]}],"parallel_tool_calls":true,"previous_response_id":null,"reasoning":{"effort":null,"summary":null},"store":true,"temperature":1.0,"text":{"format":{"type":"text"}},"tool_choice":"auto","tools":[],"top_p":1.0,"truncation":"disabled","usage":null,"user":null,"metadata":{}}}',
            '',
        ]);
    }
}
