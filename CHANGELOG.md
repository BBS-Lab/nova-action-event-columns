# Changelog

All notable changes to `bbs-lab/nova-action-event-columns` will be documented in this file.

## v1.0.0 - 2026-07-22

First stable release of **Nova Action Event Columns** — add extra columns to Nova's `action_events` table and fill them automatically on every action, across **all** Nova write paths, on **Nova 4 and Nova 5**.

### ✨ Features

- **Built-in `ip_address` column** — captures `request()->ip()` on every action event, toggleable via config.
- **Column registry** — register your own columns (`tenant_id`, `user_agent`, …) with a value resolver and an optional Nova field factory, no forking required.
- **Every write path covered** — a `creating` hook for the `->save()` paths (create/update/attach/…) and an `insert()` override for the mass-insert paths (delete/force-delete/restore jobs and every custom Nova action).
- **Custom `ActionResource`** — surfaces the registered columns in Nova, honouring each column's registered field or a sensible read-only default.
- **Retention** — an `action-events:prune` Artisan command (`--days` / `--hours` / `--all`, with production confirmation).
- **Publishable** — migration, custom-column migration stub and config.

### ✅ Quality

- **100% line coverage**, mutation tested (MSI ≥ 80%), PHPStan level 8, Pint.
- Verified in CI on **Nova 5** (Laravel 11/12/13, PHP 8.3/8.4) and **Nova 4** (Laravel 11, PHP 8.4).

### 📦 Requirements

PHP `^8.2` · Laravel Nova `^4.0 || ^5.0` · Laravel `^11.0 || ^12.0 || ^13.0`

> Nova 4 (through its Inertia dependency) tops out at PHP 8.4 and Laravel 11; on PHP 8.5 or Laravel 12+, use Nova 5.
