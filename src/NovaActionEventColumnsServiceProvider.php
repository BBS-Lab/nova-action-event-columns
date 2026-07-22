<?php

declare(strict_types=1);

namespace BBSLab\NovaActionEventColumns;

use BBSLab\NovaActionEventColumns\Console\Commands\PruneActionEventsCommand;
use BBSLab\NovaActionEventColumns\Nova\ActionResource;
use BBSLab\NovaActionEventColumns\Support\ColumnRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Nova;

class NovaActionEventColumnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/nova-action-event-columns.php',
            'nova-action-event-columns',
        );

        $this->app->singleton(ColumnRegistry::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'nova-action-event-columns');

        if ((bool) config('nova-action-event-columns.ip_address.enabled', true)) {
            $this->app->make(ColumnRegistry::class)->register(
                'ip_address',
                static fn (?Request $request): ?string => $request?->ip(),
                static fn (): Text => Text::make(
                    (string) __('nova-action-event-columns::resource.fields.ip_address'),
                    'ip_address',
                )->exceptOnForms(),
            );
        }

        // Surface the resource in Nova automatically so the recorded columns are
        // browsable with no app-side wiring. Opt out via config to keep Nova's
        // default (events only on a resource's detail page) or register your own.
        if (class_exists(Nova::class) && (bool) config('nova-action-event-columns.register_resource', true)) {
            Nova::resources([ActionResource::class]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../database/migrations/add_ip_address_to_action_events_table.php' => database_path(
                'migrations/'.date('Y_m_d_His').'_add_ip_address_to_action_events_table.php'
            ),
        ], 'nova-action-event-columns-migrations');

        $this->publishes([
            __DIR__.'/../database/migrations/add_column_to_action_events_table.php.stub' => database_path(
                'migrations/'.date('Y_m_d_His').'_add_column_to_action_events_table.php'
            ),
        ], 'nova-action-event-columns-stub');

        $this->publishes([
            __DIR__.'/../config/nova-action-event-columns.php' => config_path('nova-action-event-columns.php'),
        ], 'nova-action-event-columns-config');

        $this->publishes([
            __DIR__.'/../lang' => lang_path('vendor/nova-action-event-columns'),
        ], 'nova-action-event-columns-translations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneActionEventsCommand::class,
            ]);
        }
    }
}
