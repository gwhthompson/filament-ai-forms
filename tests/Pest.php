<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

/**
 * Call a protected method on an object using closure binding.
 *
 * This avoids reflection and uses PHP's native closure binding to access
 * protected methods in a cleaner, more readable way.
 *
 * @see https://www.yellowduck.be/posts/another-way-of-accessing-private-and-protected-properties-in-php
 */
function callProtected(object $object, string $method, mixed ...$args): mixed
{
    return (fn () => $this->{$method}(...$args))->call($object);
}
