<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Enums;

/**
 * OpenAI service tier options for API requests.
 *
 * Controls the quality of service and pricing tier for API calls.
 * Different tiers offer various trade-offs between cost, latency, and throughput.
 *
 * @see https://platform.openai.com/docs/api-reference/responses/create
 */
enum ServiceTier: string
{
    /** Automatically select the best tier based on usage */
    case Auto = 'auto';

    /** Standard service tier - balanced cost and performance */
    case Default = 'default';

    /** Flexible tier - cost-optimized for batch workloads */
    case Flex = 'flex';

    /** Scale tier - higher throughput for production workloads */
    case Scale = 'scale';

    /** Priority tier - lowest latency, highest priority processing */
    case Priority = 'priority';
}
