<?php

declare(strict_types=1);

namespace BBSLab\NovaActionEventColumns\Models;

use BBSLab\NovaActionEventColumns\Support\ColumnRegistry;
use Closure;

class ActionEvent extends \Laravel\Nova\Actions\ActionEvent
{
    /**
     * Fill each registered column, via its resolver, on the save() write paths
     * (create/update/attach/…) — but only when the value has not been set.
     */
    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            foreach (app(ColumnRegistry::class)->all() as $column => $resolver) {
                if ($event->{$column} === null) {
                    $event->{$column} = $resolver(request());
                }
            }
        });
    }

    /**
     * Fill each registered column on the mass insert() write paths (delete /
     * force-delete / restore jobs and every custom Nova Action) — which bypass
     * Eloquent events entirely. Existing keys win, so resolvers only fill gaps.
     *
     * @param  array<mixed>  $values
     */
    public static function insert($values): bool
    {
        $resolvers = app(ColumnRegistry::class)->all();

        if ($values !== [] && $resolvers !== []) {
            $request = request();

            $fill = static fn (array $row): array => $row + array_map(
                static fn (Closure $resolver): mixed => $resolver($request),
                $resolvers,
            );

            $values = array_is_list($values)
                ? array_map($fill, $values)
                : $fill($values);
        }

        return static::query()->insert($values);
    }
}
