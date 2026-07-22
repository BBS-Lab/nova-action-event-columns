<?php

declare(strict_types=1);

use BBSLab\NovaActionEventColumns\Facades\NovaActionEventColumns;
use BBSLab\NovaActionEventColumns\Models\ActionEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Workbench\Database\Factories\UserFactory;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // A real app would ship this column via the publishable stub migration.
    Schema::table('action_events', function (Blueprint $table): void {
        $table->unsignedBigInteger('tenant_id')->nullable();
    });

    NovaActionEventColumns::register('tenant_id', fn () => 99);
});

it('fills a custom column on the save() path', function (): void {
    $user = UserFactory::new()->create();

    ActionEvent::forResourceCreate($user, $user)->save();

    expect(DB::table('action_events')->first()->tenant_id)->toBe(99);
});

it('fills a custom column on the mass insert() path', function (): void {
    $user = UserFactory::new()->create();

    $rows = ActionEvent::forResourceDelete($user, collect([UserFactory::new()->create()]))
        ->map->getAttributes()
        ->all();

    ActionEvent::insert($rows);

    expect(DB::table('action_events')->first()->tenant_id)->toBe(99);
});

it('does not overwrite a custom column value already set on save()', function (): void {
    $user = UserFactory::new()->create();

    $event = ActionEvent::forResourceCreate($user, $user);
    $event->tenant_id = 7;
    $event->save();

    expect(DB::table('action_events')->first()->tenant_id)->toBe(7);
});
