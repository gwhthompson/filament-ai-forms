<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Enums;

/**
 * Reasoning effort levels for OpenAI models.
 *
 * Controls how much reasoning the model applies to generate responses.
 * Higher levels produce more thorough analysis but slower responses.
 *
 * @see https://platform.openai.com/docs/api-reference/responses/create
 */
enum ReasoningEffort: string
{
    /** No explicit reasoning - fastest responses */
    case None = 'none';

    /** Minimal reasoning - very fast responses */
    case Minimal = 'minimal';

    /** Low reasoning effort - fast responses with basic analysis */
    case Low = 'low';

    /** Medium reasoning - balanced speed and thoroughness (recommended) */
    case Medium = 'medium';

    /** High reasoning effort - most thorough analysis, slower responses */
    case High = 'high';
}
