<?php

declare(strict_types=1);

use BBSLab\NovaActionEventColumns\Models\ActionEvent;
use BBSLab\NovaActionEventColumns\NovaActionEventColumnsServiceProvider;
use BBSLab\NovaActionEventColumns\Support\ColumnRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Workbench\Database\Factories\UserFactory;

uses(RefreshDatabase::class);

function lastActionEvent(): object
{
    return DB::table('action_events')->orderByDesc('id')->first();
}

it('fills ip_address on the save() path via the creating hook', function (): void {
    $ip = withClientIp('203.0.113.7');
    $user = UserFactory::new()->create();

    ActionEvent::forResourceCreate($user, $user)->save();

    expect(lastActionEvent()->ip_address)->toBe($ip);
});

it('fills ip_address on the update save() path too', function (): void {
    $ip = withClientIp('198.51.100.42');
    $user = UserFactory::new()->create();

    ActionEvent::forResourceUpdate($user, $user)->save();

    expect(lastActionEvent()->ip_address)->toBe($ip);
});

it('does not overwrite an ip_address that is already set on the model', function (): void {
    withClientIp('203.0.113.7');
    $user = UserFactory::new()->create();

    $event = ActionEvent::forResourceCreate($user, $user);
    $event->ip_address = '10.0.0.1';
    $event->save();

    expect(lastActionEvent()->ip_address)->toBe('10.0.0.1');
});

it('stores a null ip_address when there is no resolvable client IP', function (): void {
    // Symfony returns null when REMOTE_ADDR is absent; the column stays nullable.
    request()->server->remove('REMOTE_ADDR');
    $user = UserFactory::new()->create();

    ActionEvent::forResourceCreate($user, $user)->save();

    expect(lastActionEvent()->ip_address)->toBeNull();
});

it('captures no ip_address end-to-end when the built-in column is disabled via config', function (): void {
    // The privacy opt-out must actually stop IP capture, not just skip registration.
    config(['nova-action-event-columns.ip_address.enabled' => false]);
    app()->forgetInstance(ColumnRegistry::class);
    app()->getProvider(NovaActionEventColumnsServiceProvider::class)->boot();

    withClientIp('203.0.113.7');
    $user = UserFactory::new()->create();

    ActionEvent::forResourceCreate($user, $user)->save();

    expect(lastActionEvent()->ip_address)->toBeNull();
});
