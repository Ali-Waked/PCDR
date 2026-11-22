<?php

declare(strict_types=1);

namespace App\Jobs\Phase5;

use App\Models\Phase5\AutomationExecution;
use App\Services\Phase5\AutomationEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[Tries(3)]
#[Backoff([1, 5, 15])]
final class ExecuteAutomationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 20;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $executionId) {}

    public function handle(AutomationEngineService $automations): void
    {
        $execution = AutomationExecution::query()->find($this->executionId);
        if ($execution === null) {
            return;
        }

        $automations->execute($execution);
    }
}
