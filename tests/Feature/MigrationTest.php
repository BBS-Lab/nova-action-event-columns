<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function ipMigrationPath(): string
{
    return dirname(__DIR__, 2).'/database/migrations/add_ip_address_to_action_events_table.php';
}

function ipMigration(): object
{
    return require ipMigrationPath();
}

it('adds a nullable ip_address column to action_events', function (): void {
    $column = collect(Schema::getColumns('action_events'))
        ->firstWhere('name', 'ip_address');

    expect($column)->not->toBeNull()
        ->and($column['nullable'])->toBeTrue();
});

it('declares the ip_address column with a 45-character length', function (): void {
    // SQLite ignores string lengths, so assert the schema definition itself.
    expect(file_get_contents(ipMigrationPath()))
        ->toContain("string('ip_address', 45)");
});

it('drops the column on down() and re-adds it on up()', function (): void {
    $migration = ipMigration();

    expect(Schema::hasColumn('action_events', 'ip_address'))->toBeTrue();

    $migration->down();
    expect(Schema::hasColumn('action_events', 'ip_address'))->toBeFalse();

    $migration->up();
    expect(Schema::hasColumn('action_events', 'ip_address'))->toBeTrue();
});

it('is idempotent: up() is a no-op when the column already exists', function (): void {
    $migration = ipMigration();

    $migration->up(); // column already present from the suite migrations

    expect(Schema::hasColumn('action_events', 'ip_address'))->toBeTrue();
});

it('is idempotent: down() is a no-op when the column is absent', function (): void {
    $migration = ipMigration();

    $migration->down();
    $migration->down(); // second call must not throw

    expect(Schema::hasColumn('action_events', 'ip_address'))->toBeFalse();
});
