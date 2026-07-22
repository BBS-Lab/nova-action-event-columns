<?php

declare(strict_types=1);

use BBSLab\NovaActionEventColumns\Nova\ActionResource;
use BBSLab\NovaActionEventColumns\NovaActionEventColumnsServiceProvider;
use Illuminate\Support\ServiceProvider;

it('translates the resource label, singular label and IP field in English', function (): void {
    app()->setLocale('en');

    expect(ActionResource::label())->toBe('Action Events')
        ->and(ActionResource::singularLabel())->toBe('Action Event')
        ->and(__('nova-action-event-columns::resource.fields.ip_address'))->toBe('IP');
});

it('translates the resource label, singular label and IP field in French', function (): void {
    app()->setLocale('fr');

    expect(ActionResource::label())->toBe("Événements d'action")
        ->and(ActionResource::singularLabel())->toBe("Événement d'action")
        ->and(__('nova-action-event-columns::resource.fields.ip_address'))->toBe('Adresse IP');
});

it('publishes the en/fr translations under their own tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(
        NovaActionEventColumnsServiceProvider::class,
        'nova-action-event-columns-translations',
    );

    expect($paths)->not->toBeEmpty();

    $source = (string) array_key_first($paths);

    expect($source)->toEndWith('lang')
        ->and(is_file($source.'/en/resource.php'))->toBeTrue()
        ->and(is_file($source.'/fr/resource.php'))->toBeTrue();
});
