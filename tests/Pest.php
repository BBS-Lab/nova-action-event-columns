<?php

declare(strict_types=1);

use BBSLab\NovaActionEventColumns\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

/**
 * Pin the client IP the resolvers will read for the duration of a test, so IP
 * assertions are deterministic (and a removed resolver fails loudly).
 */
function withClientIp(string $ip): string
{
    request()->server->set('REMOTE_ADDR', $ip);

    return $ip;
}
