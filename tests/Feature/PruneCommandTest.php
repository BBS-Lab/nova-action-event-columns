<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Workbench\App\Models\User;

uses(RefreshDatabase::class);

/**
 * Insert $count action-event rows created at $createdAt, returning nothing.
 */
function seedEvents(Carbon $createdAt, int $count = 1): void
{
    $row = [
        'batch_id' => (string) Str::orderedUuid(),
        'user_id' => 1,
        'name' => 'Delete',
        'actionable_type' => 'users',
        'actionable_id' => 1,
        'target_type' => 'users',
        'target_id' => 1,
        'model_type' => 'users',
        'model_id' => 1,
        'fields' => '',
        'status' => 'finished',
        'exception' => '',
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ];

    DB::table('action_events')->insert(array_fill(0, $count, $row));
}

function events(): int
{
    return DB::table('action_events')->count();
}

it('refuses to run with no window and no --all', function (): void {
    seedEvents(now()->subYear());

    Artisan::call('action-events:prune');

    expect(Artisan::output())->toContain('Provide --days, --hours or --all.')
        ->and(events())->toBe(1);
});

it('exits with a failure code when no window is given', function (): void {
    $this->artisan('action-events:prune')->assertExitCode(1);
});

it('rejects a non-numeric window instead of silently deleting everything', function (): void {
    seedEvents(now()->subYear());

    // '(int) "oops"' is 0 → cutoff would be "now" → the whole table. Must refuse.
    $this->artisan('action-events:prune', ['--days' => 'oops'])
        ->expectsOutputToContain('must be a positive integer')
        ->assertExitCode(1);

    expect(events())->toBe(1);
});

it('rejects a non-positive window', function (): void {
    seedEvents(now()->subYear());

    $this->artisan('action-events:prune', ['--hours' => 0])->assertExitCode(1);

    expect(events())->toBe(1);
});

it('deletes rows older than --days and keeps newer ones', function (): void {
    seedEvents(now()->subDays(2)); // older -> deleted
    seedEvents(now());            // newer -> kept

    Artisan::call('action-events:prune', ['--days' => 1]);

    expect(events())->toBe(1)
        ->and(Artisan::output())->toContain('Pruned 1 action event(s)');
});

it('deletes rows older than --hours and keeps newer ones', function (): void {
    seedEvents(now()->subHours(3)); // older -> deleted
    seedEvents(now());             // newer -> kept

    Artisan::call('action-events:prune', ['--hours' => 2]);

    expect(events())->toBe(1);
});

it('prunes across multiple chunks (more than the 1000-row limit)', function (): void {
    seedEvents(now()->subDays(2), 1001);

    Artisan::call('action-events:prune', ['--days' => 1]);

    expect(events())->toBe(0)
        ->and(Artisan::output())->toContain('Pruned 1001 action event(s)');
});

it('truncates the whole table with --all', function (): void {
    seedEvents(now(), 3);

    Artisan::call('action-events:prune', ['--all' => true]);

    // The truncate branch must return early: it must not fall through to the
    // age-based delete loop (which would print a "Pruned N …" line).
    expect(events())->toBe(0)
        ->and(Artisan::output())->toContain('Action events truncated.')
        ->and(Artisan::output())->not->toContain('Pruned');
});

it('skips the production confirmation with --force', function (): void {
    $this->app->detectEnvironment(fn () => 'production');
    seedEvents(now()->subDays(2));

    $this->artisan('action-events:prune', ['--days' => 1, '--force' => true])
        ->assertExitCode(0);

    expect(events())->toBe(0);
});

it('aborts on production when the confirmation is declined', function (): void {
    $this->app->detectEnvironment(fn () => 'production');
    seedEvents(now()->subDays(2));

    $this->artisan('action-events:prune', ['--days' => 1])
        ->expectsConfirmation('Prune action events on production?', 'no')
        ->assertExitCode(1);

    expect(events())->toBe(1);
});

it('proceeds on production when the confirmation is accepted', function (): void {
    $this->app->detectEnvironment(fn () => 'production');
    seedEvents(now()->subDays(2));

    $this->artisan('action-events:prune', ['--days' => 1])
        ->expectsConfirmation('Prune action events on production?', 'yes')
        ->assertExitCode(0);

    expect(events())->toBe(0);
});

it('falls back to the package model when no action resource is configured', function (): void {
    config(['nova.actions.resource' => null]);
    seedEvents(now()->subDays(2));

    Artisan::call('action-events:prune', ['--days' => 1]);

    expect(events())->toBe(0);
});

it('falls back to the package model when the configured resource has no $model', function (): void {
    // A misconfigured resource class without a static $model property.
    config(['nova.actions.resource' => User::class]);
    seedEvents(now()->subDays(2));

    Artisan::call('action-events:prune', ['--days' => 1]);

    expect(events())->toBe(0);
});
