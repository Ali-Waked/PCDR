<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContentStatus;
use App\Exceptions\InvalidWorkflowTransition;
use App\Models\ContentRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class TransitionContentStatus
{
    public function handle(Model $model, ContentStatus|string $target, ?User $actor = null): Model
    {
        $target = $target instanceof ContentStatus ? $target : ContentStatus::from($target);
        $current = ContentStatus::from(
            ContentStatus::valueOf($model->workflow_status ?? $model->status),
        );
        $allowed = config('cms.transitions', []);

        if (! in_array($target->value, $allowed[$current->value] ?? [], true)) {
            throw new InvalidWorkflowTransition($current, $target);
        }

        $statusColumn = Schema::hasColumn($model->getTable(), 'workflow_status') ? 'workflow_status' : 'status';

        DB::transaction(function () use ($model, $target, $actor, $statusColumn): void {
            $model->{$statusColumn} = $target;

            if ($target === ContentStatus::Published) {
                $model->published_at ??= now();
            }

            $model->save();

            ContentRevision::create([
                'revisionable_type' => $model->getMorphClass(),
                'revisionable_id' => $model->getKey(),
                'locale' => app()->getLocale(),
                'title' => $model->t('title'),
                'status' => $target->value,
                'payload' => ['status' => $target->value, 'published_at' => $model->published_at?->toISOString()],
                'actor_id' => $actor?->id,
                'note' => 'workflow transition',
                'created_at' => now(),
            ]);
        });

        return $model;
    }
}
