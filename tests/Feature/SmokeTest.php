<?php

declare(strict_types=1);

use BBSLab\NovaActionEventColumns\Models\ActionEvent;
use BBSLab\NovaActionEventColumns\Nova\ActionResource;
use BBSLab\NovaActionEventColumns\NovaActionEventColumnsServiceProvider;
use BBSLab\NovaActionEventColumns\Support\ColumnRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Nova\Nova;

uses(RefreshDatabase::class);

it('boots the service provider and loads the config', function (): void {
    expect(app()->getProvider(NovaActionEventColumnsServiceProvider::class))->not->toBeNull()
        ->and(config('nova-action-event-columns.ip_address.enabled'))->toBeTrue();
});

it('binds the column registry as a singleton', function (): void {
    expect(app(ColumnRegistry::class))->toBe(app(ColumnRegistry::class));
});

it('activates the package ActionResource and model through Nova', function (): void {
    expect(Nova::actionResource())->toBe(ActionResource::class)
        ->and(Nova::actionEvent())->toBeInstanceOf(ActionEvent::class);
});

it('has migrated the ip_address column onto the action_events table', function (): void {
    expect(Schema::hasColumn('action_events', 'ip_address'))->toBeTrue();
});
