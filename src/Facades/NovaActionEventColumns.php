<?php

declare(strict_types=1);

namespace BBSLab\NovaActionEventColumns\Facades;

use BBSLab\NovaActionEventColumns\Support\ColumnRegistry;
use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void register(string $column, Closure $resolver, ?Closure $field = null)
 * @method static array<int, string> columns()
 * @method static void forget(string $column)
 *
 * @see ColumnRegistry
 */
class NovaActionEventColumns extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ColumnRegistry::class;
    }
}
