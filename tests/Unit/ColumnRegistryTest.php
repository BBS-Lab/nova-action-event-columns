<?php

declare(strict_types=1);

use BBSLab\NovaActionEventColumns\Facades\NovaActionEventColumns;
use BBSLab\NovaActionEventColumns\Support\ColumnRegistry;
use Laravel\Nova\Fields\Text;

beforeEach(function (): void {
    $this->registry = new ColumnRegistry;
});

it('registers a column with a resolver and exposes it through all()/columns()', function (): void {
    $this->registry->register('tenant_id', fn () => 42);

    expect($this->registry->columns())->toBe(['tenant_id'])
        ->and($this->registry->all())->toHaveKey('tenant_id')
        ->and(($this->registry->all()['tenant_id'])(null))->toBe(42);
});

it('stores a null field factory when none is given', function (): void {
    $this->registry->register('tenant_id', fn () => 42);

    expect($this->registry->fields())->toBe(['tenant_id' => null]);
});

it('keeps the provided field factory', function (): void {
    $factory = fn () => Text::make('Tenant', 'tenant_id');
    $this->registry->register('tenant_id', fn () => 42, $factory);

    expect($this->registry->fields()['tenant_id'])->toBe($factory);
});

it('forgets a registered column', function (): void {
    $this->registry->register('tenant_id', fn () => 42);
    $this->registry->forget('tenant_id');

    expect($this->registry->columns())->toBe([])
        ->and($this->registry->all())->toBe([])
        ->and($this->registry->fields())->toBe([]);
});

it('overwrites a column when registered twice', function (): void {
    $this->registry->register('tenant_id', fn () => 1);
    $this->registry->register('tenant_id', fn () => 2);

    expect($this->registry->columns())->toBe(['tenant_id'])
        ->and(($this->registry->all()['tenant_id'])(null))->toBe(2);
});

it('resolves the facade to the bound registry singleton', function (): void {
    expect(NovaActionEventColumns::getFacadeRoot())->toBe(app(ColumnRegistry::class));
});
