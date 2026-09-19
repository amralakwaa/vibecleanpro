<?php

namespace App\Support\Launch;

final class LaunchPlan
{
    /** @var list<LaunchStep> */
    public array $steps = [];

    /** @var list<string> */
    public array $problems = [];

    public function step(string $section, string $target, string $action, bool $changes): void
    {
        $this->steps[] = new LaunchStep($section, $target, $action, $changes);
    }

    public function problem(string $message): void
    {
        $this->problems[] = $message;
    }

    public function hasProblems(): bool
    {
        return $this->problems !== [];
    }

    /**
     * @return list<LaunchStep>
     */
    public function changes(): array
    {
        return array_values(array_filter($this->steps, fn (LaunchStep $step) => $step->changes));
    }
}
