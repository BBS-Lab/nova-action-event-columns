<?php

declare(strict_types=1);

use BBSLab\NovaActionEventColumns\Models\ActionEvent;
use BBSLab\NovaActionEventColumns\Support\ColumnRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Http\Requests\ActionRequest;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

uses(RefreshDatabase::class);

/**
 * Build the exact row shape the Delete/Restore jobs feed to insert():
 * forResourceDelete()->map->getAttributes()->all(), which carries no ip_address.
 *
 * @return array<int, array<string, mixed>>
 */
function deleteRows(User $user, iterable $models): array
{
    return ActionEvent::forResourceDelete($user, collect($models))
        ->map->getAttributes()
        ->all();
}

it('fills ip_address on every row of a mass insert() (list form)', function (): void {
    $ip = withClientIp('203.0.113.7');
    $user = UserFactory::new()->create();
    $models = UserFactory::new()->count(3)->create();

    $rows = deleteRows($user, $models);
    // Sanity: the source rows really do not carry the column themselves.
    expect($rows[0])->not->toHaveKey('ip_address');

    ActionEvent::insert($rows);

    $stored = DB::table('action_events')->get();
    expect($stored)->toHaveCount(3);
    $stored->each(fn ($row) => expect($row->ip_address)->toBe($ip));
});

it('fills ip_address on a single associative row (not a list)', function (): void {
    $ip = withClientIp('192.0.2.55');
    $user = UserFactory::new()->create();

    ActionEvent::insert(deleteRows($user, [UserFactory::new()->create()])[0]);

    expect(DB::table('action_events')->count())->toBe(1)
        ->and(DB::table('action_events')->first()->ip_address)->toBe($ip);
});

it('does not overwrite an ip_address already present on an inserted row', function (): void {
    withClientIp('203.0.113.7');
    $user = UserFactory::new()->create();

    $row = deleteRows($user, [UserFactory::new()->create()])[0];
    $row['ip_address'] = '10.0.0.9';

    ActionEvent::insert($row);

    expect(DB::table('action_events')->first()->ip_address)->toBe('10.0.0.9');
});

it('inserts nothing (and does not throw) for an empty value set', function (): void {
    withClientIp('203.0.113.7');

    expect(ActionEvent::insert([]))->toBeTrue()
        ->and(DB::table('action_events')->count())->toBe(0);
});

it('leaves rows untouched when no columns are registered', function (): void {
    withClientIp('203.0.113.7');
    app(ColumnRegistry::class)->forget('ip_address');

    $user = UserFactory::new()->create();
    ActionEvent::insert(deleteRows($user, [UserFactory::new()->create()]));

    expect(DB::table('action_events')->first()->ip_address)->toBeNull();
});

it('fills ip_address through the real createForModels() action path', function (): void {
    $ip = withClientIp('203.0.113.7');
    $user = UserFactory::new()->create();
    $target = UserFactory::new()->create();

    $request = new class extends ActionRequest
    {
        public User $stubModel;

        public function isPivotAction(): bool
        {
            return false;
        }

        public function model()
        {
            return $this->stubModel;
        }

        public function resolveFieldsForStorage(): array
        {
            return [];
        }
    };
    $request->stubModel = $target;
    $request->setContainer(app());
    $request->setUserResolver(fn () => $user);

    $action = new Action;
    $action->name = 'Test Action';

    ActionEvent::createForModels($request, $action, (string) Str::orderedUuid(), collect([$target]));

    $stored = DB::table('action_events')->get();
    expect($stored)->toHaveCount(1)
        ->and($stored->first()->ip_address)->toBe($ip)
        ->and($stored->first()->name)->toBe('Test Action');
});
