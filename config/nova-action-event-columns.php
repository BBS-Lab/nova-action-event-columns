<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-register the Nova resource
    |--------------------------------------------------------------------------
    |
    | When true, the package registers its ActionResource with Nova and surfaces
    | it in the navigation, so the recorded columns are browsable without any
    | app-side wiring. Set to false to keep Nova's default (events only visible
    | on a resource's detail page) or to register your own resource instead.
    |
    */

    'register_resource' => env('NOVA_ACTION_EVENT_COLUMNS_REGISTER_RESOURCE', true),

    /*
    |--------------------------------------------------------------------------
    | Built-in IP address column
    |--------------------------------------------------------------------------
    |
    | When enabled, the package registers a resolver that stores the client IP
    | (request()->ip()) on every action event, and shows it read-only in the
    | ActionResource. Disable it to opt out of the built-in column entirely.
    |
    | Custom columns are registered in code via the NovaActionEventColumns
    | facade — resolvers are closures and cannot live in a (cacheable) config.
    |
    */

    'ip_address' => [
        'enabled' => env('NOVA_ACTION_EVENT_COLUMNS_IP_ENABLED', true),
    ],

];
