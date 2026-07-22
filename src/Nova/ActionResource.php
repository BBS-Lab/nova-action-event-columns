<?php

declare(strict_types=1);

namespace BBSLab\NovaActionEventColumns\Nova;

use BBSLab\NovaActionEventColumns\Models\ActionEvent;
use BBSLab\NovaActionEventColumns\Support\ColumnRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @extends \Laravel\Nova\Actions\ActionResource<ActionEvent>
 */
class ActionResource extends \Laravel\Nova\Actions\ActionResource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<ActionEvent>
     */
    public static $model = ActionEvent::class;

    /**
     * Nova hides its action-event resource from navigation by default; this
     * package exists to surface the recorded columns, so show it.
     */
    #[\Override]
    public static function availableForNavigation(Request $request)
    {
        return true;
    }

    #[\Override]
    public static function label()
    {
        return (string) __('nova-action-event-columns::resource.label');
    }

    #[\Override]
    public static function singularLabel()
    {
        return (string) __('nova-action-event-columns::resource.singular');
    }

    /**
     * @return array<int, Field>
     */
    #[\Override]
    public function fields(NovaRequest $request)
    {
        $registry = app(ColumnRegistry::class);
        $fields = $registry->fields();

        $extra = array_map(
            static fn (?Closure $factory, string $column): Field => $factory !== null
                ? $factory()
                : Text::make(Str::headline($column), $column)->exceptOnForms(),
            $fields,
            array_keys($fields),
        );

        return array_merge(parent::fields($request), $extra);
    }
}
