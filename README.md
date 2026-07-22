# Nova Action Event Columns

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bbs-lab/nova-action-event-columns.svg?style=flat-square)](https://packagist.org/packages/bbs-lab/nova-action-event-columns)
[![Tests](https://img.shields.io/github/actions/workflow/status/BBS-Lab/nova-action-event-columns/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/BBS-Lab/nova-action-event-columns/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/bbs-lab/nova-action-event-columns.svg?style=flat-square)](https://packagist.org/packages/bbs-lab/nova-action-event-columns)

Add **extra columns to [Laravel Nova](https://nova.laravel.com)'s `action_events` table** and fill them
automatically on every action. The **client IP address** (`ip_address`) ships as a built-in column; your
app can register **additional columns** — `tenant_id`, `user_agent`, anything — without forking, through a
small resolver registry.

Nova writes `action_events` through two internal paths (event-firing `->save()` and event-bypassing mass
`::insert()`), and only fires Eloquent events on one of them. This package hooks **both**, so your columns
are populated no matter how the event is created: create, update, attach, delete, force-delete, restore,
and every custom Nova action. **Works on Nova 4 and Nova 5.**

## Features

- 🌐 Built-in **`ip_address`** column captured from `request()->ip()`, toggleable via config
- 🧩 **Column registry** — register your own columns with a value resolver and an optional Nova field
- 🔁 **Every write path covered** — a `creating` hook for `->save()` paths and an `insert()` override for the mass-insert paths
- 🖥️ Custom **`ActionResource`** that surfaces your columns in Nova (your field, or a read-only default)
- 🧹 **`action-events:prune`** command for retention (`--days` / `--hours` / `--all`)
- 📦 Publishable migration, custom-column migration **stub**, and config
- 🧪 100% line coverage, PHPStan level 8, no `final` classes, strict types everywhere

## Requirements

- PHP `^8.2`
- Laravel Nova `^4.0 || ^5.0`
- Laravel `^11.0 || ^12.0 || ^13.0`

Both Nova majors are exercised in CI. Note that **Nova 4** (through its `inertiajs/inertia-laravel`
dependency) tops out at **PHP 8.4** and **Laravel 11**; on PHP 8.5 or Laravel 12+, use Nova 5. Composer
resolves the right combination for you.

## Installation

Because Nova is a paid, private package, make sure your application is already authenticated against
`nova.laravel.com`, then:

```bash
composer require bbs-lab/nova-action-event-columns
```

The service provider auto-registers via Laravel package discovery. The `ip_address` migration runs
automatically. Publish the config, migration or custom-column stub if you want to tweak them:

```bash
# Config
php artisan vendor:publish --tag=nova-action-event-columns-config

# The shipped ip_address migration
php artisan vendor:publish --tag=nova-action-event-columns-migrations

# A stub for adding your own action_events column — edit it before migrating
php artisan vendor:publish --tag=nova-action-event-columns-stub

php artisan migrate
```

## Activation

Nova only records into a custom `action_events` model when you point its action resource at this
package. In `config/nova.php`:

```php
'actions' => [
    'resource' => \BBSLab\NovaActionEventColumns\Nova\ActionResource::class,
],
```

Then run `php artisan migrate`. Until this is set, Nova uses its own action resource and your extra
columns stay `null`.

## Usage

### The built-in IP column

Once activated and migrated, every action event stores the request IP in `ip_address` — nothing else to
do. Toggle it in `config/nova-action-event-columns.php` (env `NOVA_ACTION_EVENT_COLUMNS_IP_ENABLED`).

### Registering custom columns

Register columns from a service provider's `boot()` — **in code, never in config**, because closures
cannot be serialized by `config:cache`. Each column gets a **resolver** (the value to store) and an
optional **field factory** (how it shows in Nova):

```php
use BBSLab\NovaActionEventColumns\Facades\NovaActionEventColumns;
use Laravel\Nova\Fields\Number;

public function boot(): void
{
    NovaActionEventColumns::register(
        'tenant_id',
        fn ($request) => $request?->user()?->tenant_id,       // value resolver
        fn () => Number::make('Tenant', 'tenant_id')->exceptOnForms(), // optional Nova field
    );
}
```

- The resolver receives the current `Illuminate\Http\Request` (or `null` outside an HTTP context — return
  `null` and leave the column nullable).
- When you omit the field factory, the column is shown read-only via
  `Text::make(Str::headline($column), $column)`.
- Existing values are never overwritten — resolvers only fill columns that are still `null`.

You must provision the database column yourself; publish the stub
(`--tag=nova-action-event-columns-stub`), rename it, set the column name/type, and migrate.

### Displaying columns in Nova

The package's `Nova\ActionResource` extends Nova's own and adds a field for each registered column —
your registered field if you supplied one, otherwise the read-only default. The built-in `ip_address`
ships as an "IP" field.

### The `action-events:prune` command

Retention for the `action_events` table:

```bash
# Delete events older than 365 days (chunked)
php artisan action-events:prune --days=365

# Or by hours
php artisan action-events:prune --hours=48

# Wipe everything (TRUNCATE, resets the auto-increment)
php artisan action-events:prune --all

# Skip the production confirmation prompt
php artisan action-events:prune --days=365 --force
```

A window (`--days` / `--hours`) or `--all` is required — with neither, the command refuses. It is
**never auto-scheduled**; wire it up yourself if you want it periodic:

```php
// routes/console.php
Schedule::command('action-events:prune --days=365 --force')->daily();
```

### TrustProxies caveat

`request()->ip()` reflects the real client only if your app trusts its proxies. Behind a load balancer,
configure trusted proxies so the forwarded header is honoured:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR);
})
```

If you trust proxies as `*`, the forwarded IP is client-spoofable — treat `ip_address` as a best-effort
audit signal, not hard proof.

## Testing

```bash
composer test            # Pest suite
composer test-coverage   # 100% line coverage on src/
composer analyse         # PHPStan level 8
composer format          # Pint (laravel preset + strict types)
```

A full embedded Nova app (via [Orchestra Workbench](https://github.com/orchestral/workbench)) lets you
exercise the flow in a real Nova instance:

```bash
composer serve   # boots Nova at http://localhost:8000/nova
```

## Security

The built-in `ip_address` is captured on a best-effort basis and is only trustworthy when proxies are
configured correctly (see the TrustProxies caveat). If you discover a security issue, please email
`paris@big-boss-studio.com` instead of using the issue tracker.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Big Boss Studio](https://github.com/BBS-Lab)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
