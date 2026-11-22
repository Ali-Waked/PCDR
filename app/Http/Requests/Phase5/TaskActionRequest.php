<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

abstract class TaskActionRequest extends Phase5Request
{
    protected function authorizeTask(string $permission): bool
    {
        $user = $this->user();
        $task = $this->routeTask();

        if ($user === null || $task === null || ! $this->allows($permission)) {
            return false;
        }

        $this->phase5Auth()->assertCanViewResource($user, 'task', (int) $task->getKey());

        return true;
    }
}
