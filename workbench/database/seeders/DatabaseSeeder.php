<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use BBSLab\NovaActionEventColumns\Models\ActionEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a Nova login user plus a batch of action events across several client
     * IPs, so `composer serve` shows a populated "Action Events" screen with the
     * extra ip_address column filled on every row. Log in as nova@laravel.com
     * (password "password").
     *
     * A real request supplies the IP; the seeder runs in the console, so it fakes
     * one per batch (the same way the feature tests do) to exercise both write
     * paths — save() (Create/Update) and the mass insert() (Delete).
     */
    public function run(): void
    {
        DB::table('action_events')->delete();

        $admin = User::query()->firstOrCreate(
            ['email' => 'nova@laravel.com'],
            ['name' => 'Laravel Nova', 'password' => 'password'],
        );

        $alice = User::query()->firstOrCreate(
            ['email' => 'alice@example.com'],
            ['name' => 'Alice Martin', 'password' => 'password'],
        );

        $bob = User::query()->firstOrCreate(
            ['email' => 'bob@example.com'],
            ['name' => 'Bob Dupont', 'password' => 'password'],
        );

        /** @var list<array{string, User}> $batches */
        $batches = [
            ['198.51.100.24', $alice],
            ['203.0.113.7', $bob],
            ['192.0.2.55', $alice],
        ];

        foreach ($batches as [$ip, $target]) {
            request()->server->set('REMOTE_ADDR', $ip);

            // save() path — the creating hook fills ip_address.
            ActionEvent::forResourceCreate($admin, $target)->save();
            ActionEvent::forResourceUpdate($admin, $target)->save();

            // mass insert() path — the insert() override fills it.
            ActionEvent::insert(
                ActionEvent::forResourceDelete($admin, collect([$target]))
                    ->map->getAttributes()
                    ->all()
            );
        }
    }
}
