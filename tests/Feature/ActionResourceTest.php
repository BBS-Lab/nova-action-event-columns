<?php

declare(strict_types=1);

use BBSLab\NovaActionEventColumns\Facades\NovaActionEventColumns;
use BBSLab\NovaActionEventColumns\Models\ActionEvent;
use BBSLab\NovaActionEventColumns\Nova\ActionResource;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @return Collection<int, Field>
 */
function resourceFields(): Collection
{
    $fields = (new ActionResource(new ActionEvent))->fields(NovaRequest::create('/'));

    return collect($fields)->filter(fn ($field) => $field instanceof Field)->values();
}

it('keeps the parent Nova action fields', function (): void {
    expect(resourceFields()->map->attribute)->toContain('name', 'status');
});

it('appends the built-in ip_address column as a read-only Text field', function (): void {
    $ip = resourceFields()->first(fn (Field $field) => $field->attribute === 'ip_address');

    expect($ip)->toBeInstanceOf(Text::class)
        ->and($ip->name)->toBe('IP')
        ->and($ip->showOnCreation)->toBeFalse()
        ->and($ip->showOnUpdate)->toBeFalse();
});

it('uses a registered custom field factory instead of the default Text field', function (): void {
    NovaActionEventColumns::register(
        'tenant_id',
        fn () => 1,
        fn () => Number::make('Tenant', 'tenant_id')->exceptOnForms(),
    );

    $tenant = resourceFields()->first(fn (Field $field) => $field->attribute === 'tenant_id');

    expect($tenant)->toBeInstanceOf(Number::class)
        ->and($tenant->name)->toBe('Tenant');
});

it('falls back to a headlined read-only Text field when no factory is registered', function (): void {
    NovaActionEventColumns::register('user_agent', fn () => 'x');

    $field = resourceFields()->first(fn (Field $field) => $field->attribute === 'user_agent');

    expect($field)->toBeInstanceOf(Text::class)
        ->and($field->name)->toBe('User Agent') // Str::headline('user_agent')
        ->and($field->showOnCreation)->toBeFalse()
        ->and($field->showOnUpdate)->toBeFalse();
});
