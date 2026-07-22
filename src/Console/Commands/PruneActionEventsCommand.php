<?php

declare(strict_types=1);

namespace BBSLab\NovaActionEventColumns\Console\Commands;

use BBSLab\NovaActionEventColumns\Models\ActionEvent;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class PruneActionEventsCommand extends Command
{
    protected $signature = 'action-events:prune {--days= : Prune events older than this many days} {--hours= : Prune events older than this many hours} {--all : Truncate the whole table, resetting the auto-increment} {--force : Skip the production confirmation}';

    protected $description = 'Prune old Nova action events by age, or truncate them entirely.';

    public function handle(): int
    {
        $days = $this->option('days');
        $hours = $this->option('hours');
        $all = (bool) $this->option('all');
        $force = (bool) $this->option('force');

        if (! $all && $days === null && $hours === null) {
            $this->error('Provide --days, --hours or --all.');

            return self::FAILURE;
        }

        // Guard a destructive command against a mistyped window: a non-numeric
        // value would cast to 0 and silently delete every row before "now".
        foreach (['days' => $days, 'hours' => $hours] as $name => $value) {
            if ($value === null) {
                continue;
            }

            if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
                $this->error("The --{$name} option must be a positive integer.");

                return self::FAILURE;
            }
        }

        if ($this->getLaravel()->environment('production') && ! $force
            && ! $this->confirm('Prune action events on production?')) {
            return self::FAILURE;
        }

        $query = $this->model()->newQuery();

        if ($all) {
            $query->truncate();

            $this->info('Action events truncated.');

            return self::SUCCESS;
        }

        $cutoff = now()
            ->subDays((int) $days)
            ->subHours((int) $hours);

        $deleted = 0;

        do {
            $count = (clone $query)
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();

            $deleted += $count;
        } while ($count > 0);

        $this->info("Pruned {$deleted} action event(s) older than {$cutoff->toDateTimeString()}.");

        return self::SUCCESS;
    }

    /**
     * Resolve the configured action-event model (falling back to ours).
     */
    protected function model(): Model
    {
        /** @var class-string|null $resource */
        $resource = config('nova.actions.resource');

        /** @var class-string<Model> $model */
        $model = $resource !== null && property_exists($resource, 'model')
            ? $resource::$model
            : ActionEvent::class;

        return new $model;
    }
}
