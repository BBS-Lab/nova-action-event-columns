<?php

declare(strict_types=1);

use BBSLab\NovaActionEventColumns\Console\Commands\PruneActionEventsCommand;
use BBSLab\NovaActionEventColumns\Nova\ActionResource;
use BBSLab\NovaActionEventColumns\NovaActionEventColumnsServiceProvider;
use BBSLab\NovaActionEventColumns\Support\ColumnRegistry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Nova;

function rebootProvider(): void
{
    /** @var NovaActionEventColumnsServiceProvider $provider */
    $provider = app()->getProvider(NovaActionEventColumnsServiceProvider::class);
    $provider->boot();
}

it('registers the built-in ip_address resolver when enabled', function (): void {
    expect(app(ColumnRegistry::class)->columns())->toContain('ip_address');
});

it('auto-registers its ActionResource with Nova and makes it navigable', function (): void {
    expect(Nova::$resources)->toContain(ActionResource::class)
        ->and(ActionResource::availableForNavigation(request()))->toBeTrue();
});

it('does not auto-register the resource when disabled', function (): void {
    $original = Nova::$resources;
    Nova::$resources = [];

    try {
        config(['nova-action-event-columns.register_resource' => false]);
        rebootProvider();

        expect(Nova::$resources)->not->toContain(ActionResource::class);
    } finally {
        Nova::$resources = $original;
    }
});

it('does not register the ip_address resolver when it is disabled', function (): void {
    config(['nova-action-event-columns.ip_address.enabled' => false]);
    app()->forgetInstance(ColumnRegistry::class);

    rebootProvider();

    expect(app(ColumnRegistry::class)->columns())->not->toContain('ip_address');
});

it('reads the IP toggle from the env var documented in the README', function (): void {
    // Pins the config env key to the README's NOVA_ACTION_EVENT_COLUMNS_IP_ENABLED;
    // a drift makes the documented privacy opt-out a silent no-op (IP is PII).
    $_SERVER['NOVA_ACTION_EVENT_COLUMNS_IP_ENABLED'] = 'false';
    $_ENV['NOVA_ACTION_EVENT_COLUMNS_IP_ENABLED'] = 'false';

    try {
        $config = require __DIR__.'/../../config/nova-action-event-columns.php';
        expect($config['ip_address']['enabled'])->toBeFalse();
    } finally {
        unset($_SERVER['NOVA_ACTION_EVENT_COLUMNS_IP_ENABLED'], $_ENV['NOVA_ACTION_EVENT_COLUMNS_IP_ENABLED']);
    }
});

it('registers the ip_address resolver by default when the config key is absent', function (): void {
    // Removing the key entirely must fall back to the "true" default.
    config(['nova-action-event-columns.ip_address' => []]);
    app()->forgetInstance(ColumnRegistry::class);

    rebootProvider();

    expect(app(ColumnRegistry::class)->columns())->toContain('ip_address');
});

it('resolves a null ip_address when there is no request to read from', function (): void {
    $resolve = app(ColumnRegistry::class)->all()['ip_address'];

    // The resolver must null-safe the request, not dereference null.
    expect($resolve(null))->toBeNull();
});

it('registers the prune command', function (): void {
    expect(Artisan::all())->toHaveKey('action-events:prune')
        ->and(Artisan::all()['action-events:prune'])->toBeInstanceOf(PruneActionEventsCommand::class);
});

it('publishes the built-in ip_address migration under its own tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(
        NovaActionEventColumnsServiceProvider::class,
        'nova-action-event-columns-migrations',
    );

    expect($paths)->not->toBeEmpty();

    $source = (string) array_key_first($paths);

    expect($source)->toEndWith('database/migrations/add_ip_address_to_action_events_table.php')
        ->and(is_file($source))->toBeTrue()
        ->and((string) $paths[$source])
        ->toMatch('#/migrations/\d{4}_\d{2}_\d{2}_\d{6}_add_ip_address_to_action_events_table\.php$#');
});

it('publishes the custom-column stub under its own tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(
        NovaActionEventColumnsServiceProvider::class,
        'nova-action-event-columns-stub',
    );

    $source = (string) array_key_first($paths);

    expect($paths)->not->toBeEmpty()
        ->and($source)->toEndWith('add_column_to_action_events_table.php.stub')
        ->and(is_file($source))->toBeTrue()
        ->and((string) $paths[$source])
        ->toMatch('#/migrations/\d{4}_\d{2}_\d{2}_\d{6}_add_column_to_action_events_table\.php$#');
});

it('publishes the config under its own tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(
        NovaActionEventColumnsServiceProvider::class,
        'nova-action-event-columns-config',
    );

    $source = (string) array_key_first($paths);

    expect($paths)->not->toBeEmpty()
        ->and($source)->toEndWith('config/nova-action-event-columns.php')
        ->and(is_file($source))->toBeTrue() // absolute __DIR__-based source path
        ->and((string) $paths[$source])->toEndWith('config'.DIRECTORY_SEPARATOR.'nova-action-event-columns.php');
});
