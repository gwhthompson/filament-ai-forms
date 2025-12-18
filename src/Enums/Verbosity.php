<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Enums;

/**
 * Verbosity levels for AI-generated text output.
 *
 * Controls the length and detail level of generated content.
 * Use lower verbosity for concise responses and higher for detailed explanations.
 *
 * @see https://platform.openai.com/docs/api-reference/responses/create
 */
enum Verbosity: string
{
    /** Concise output - short, to-the-point responses */
    case Low = 'low';

    /** Balanced output - moderate detail level (recommended for most use cases) */
    case Medium = 'medium';

    /** Detailed output - comprehensive, thorough responses */
    case High = 'high';
}
