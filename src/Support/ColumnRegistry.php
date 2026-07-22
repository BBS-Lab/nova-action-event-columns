<?php

declare(strict_types=1);

namespace BBSLab\NovaActionEventColumns\Support;

use Closure;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Field;

class ColumnRegistry
{
    /**
     * The registered columns, keyed by column name.
     *
     * @var array<string, array{resolver: Closure, field: Closure|null}>
     */
    protected array $columns = [];

    /**
     * Register a column with its value resolver and an optional Nova field factory.
     *
     * @param  Closure(Request|null): mixed  $resolver
     * @param  (Closure(): Field)|null  $field
     */
    public function register(string $column, Closure $resolver, ?Closure $field = null): void
    {
        $this->columns[$column] = ['resolver' => $resolver, 'field' => $field];
    }

    /**
     * The registered value resolvers, keyed by column name.
     *
     * @return array<string, Closure>
     */
    public function all(): array
    {
        return array_map(static fn (array $entry): Closure => $entry['resolver'], $this->columns);
    }

    /**
     * The registered Nova field factories (may be null), keyed by column name.
     *
     * @return array<string, Closure|null>
     */
    public function fields(): array
    {
        return array_map(static fn (array $entry): ?Closure => $entry['field'], $this->columns);
    }

    /**
     * The registered column names.
     *
     * @return array<int, string>
     */
    public function columns(): array
    {
        return array_keys($this->columns);
    }

    public function forget(string $column): void
    {
        unset($this->columns[$column]);
    }
}
