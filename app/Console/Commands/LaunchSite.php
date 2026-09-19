<?php

namespace App\Console\Commands;

use App\Support\Launch\LaunchAbortedException;
use App\Support\Launch\LaunchManifest;
use App\Support\Launch\LaunchPlan;
use App\Support\Launch\LaunchPlanner;
use App\Support\Launch\LaunchStep;
use Illuminate\Console\Command;
use InvalidArgumentException;

class LaunchSite extends Command
{
    protected $signature = 'site:launch
        {--mode=dry-run : dry-run (show the plan), validate (problems only, exit code) or apply (write)}
        {--manifest= : Path to the launch manifest (defaults to database/seeders/content/launch-manifest.json)}';

    protected $description = 'Apply the owner-approved launch manifest: confirm projects, link articles and publish pages through the Publishing Gate';

    public function handle(LaunchPlanner $planner): int
    {
        $mode = (string) $this->option('mode');

        if (! in_array($mode, ['dry-run', 'validate', 'apply'], true)) {
            $this->error('--mode must be dry-run, validate or apply.');

            return self::INVALID;
        }

        try {
            $manifest = LaunchManifest::load($this->option('manifest') ?: null);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($mode === 'apply') {
            try {
                $plan = $planner->apply($manifest);
            } catch (LaunchAbortedException $exception) {
                $this->reportProblems($exception->plan);
                $this->error('Nothing was changed.');

                return self::FAILURE;
            }

            $this->renderPlan($plan);
            $this->info(sprintf('Applied: %d change(s).', count($plan->changes())));

            return self::SUCCESS;
        }

        $plan = $planner->plan($manifest);

        if ($mode === 'dry-run') {
            $this->renderPlan($plan);
        }

        if ($plan->hasProblems()) {
            $this->reportProblems($plan);

            return self::FAILURE;
        }

        $this->info(sprintf('%s: valid — %d change(s) would be applied, %d item(s) already in place.', $mode === 'validate' ? 'Validation' : 'Dry run', count($plan->changes()), count($plan->steps) - count($plan->changes())));

        return self::SUCCESS;
    }

    private function renderPlan(LaunchPlan $plan): void
    {
        $this->table(['Section', 'Item', 'Action'], array_map(
            fn (LaunchStep $step) => [$step->section, $step->target, ($step->changes ? '→ ' : '  ').$step->action],
            $plan->steps,
        ));
    }

    private function reportProblems(LaunchPlan $plan): void
    {
        $this->error(sprintf('%d problem(s) — launch refused:', count($plan->problems)));

        foreach ($plan->problems as $problem) {
            $this->line('  • '.$problem);
        }
    }
}
