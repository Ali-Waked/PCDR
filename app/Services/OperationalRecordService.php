<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OperationalRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Creation, update, and removal of config-declared operational records.
 */
final class OperationalRecordService
{
    public function __construct(
        private readonly OperationCatalog $catalog,
        private readonly OperationalTranslationSaver $translations,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $translationInput
     */
    public function create(string $resource, array $attributes, array $translationInput = []): OperationalRecord
    {
        $fields = $this->catalog->fields($resource);
        $clean = Arr::only($attributes, $fields);

        $record = DB::transaction(function () use ($resource, $clean, $translationInput): OperationalRecord {
            $record = $this->catalog->newRecord($resource, $clean);
            $record->save();

            if ($this->catalog->isTranslatable($resource) && $translationInput !== []) {
                $this->translations->sync($resource, $record, $translationInput);
            }

            return $record;
        });

        $this->audit->record(
            'operations.record_created',
            $resource,
            (string) $record->getKey(),
            null,
            $this->auditSnapshot($record),
        );

        return $record;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $translationInput
     */
    public function update(string $resource, OperationalRecord $record, array $attributes, array $translationInput = []): OperationalRecord
    {
        $before = $this->auditSnapshot($record);
        $clean = Arr::only($attributes, $this->catalog->fields($resource));

        DB::transaction(function () use ($resource, $record, $clean, $translationInput): void {
            $record->fill($clean)->save();

            if ($this->catalog->isTranslatable($resource) && $translationInput !== []) {
                $this->translations->sync($resource, $record, $translationInput);
            }
        });

        $this->audit->record(
            'operations.record_updated',
            $resource,
            (string) $record->getKey(),
            $before,
            $this->auditSnapshot($record),
        );

        return $record->refresh();
    }

    public function delete(string $resource, OperationalRecord $record): void
    {
        $before = $this->auditSnapshot($record);
        $record->delete();

        $this->audit->record(
            'operations.record_deleted',
            $resource,
            (string) $record->getKey(),
            $before,
            null,
        );
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(OperationalRecord $record): array
    {
        return [
            'id' => $record->getKey(),
            'resource' => $record->getResource(),
            'status' => $record->getAttribute('status'),
        ];
    }
}
