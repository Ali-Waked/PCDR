<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasOperationalTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * Config-bound record for the operational modules.
 *
 * This is not an untyped catch-all: every instance is bound to exactly one
 * resource declared in config/operations.php, which fixes its table, fillable
 * columns, statuses, and authorization family. Dedicated models are used for
 * the inventory ledger, translations, documents, and transition history.
 */
class OperationalRecord extends Model
{
    use HasOperationalTranslations;
    use SoftDeletes;

    protected $guarded = [];

    protected string $resource = '';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if ($this->resource !== '') {
            $this->configureResource($this->resource);
        }
    }

    public static function forResource(string $resource): self
    {
        $model = new self;
        $model->configureResource($resource);

        return $model;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    /** @param array<string, mixed> $attributes @return static */
    public function newInstance($attributes = [], $exists = false)
    {
        $instance = parent::newInstance($attributes, $exists);
        if ($instance instanceof self && $this->resource !== '') {
            $instance->configureResource($this->resource);
        }

        return $instance;
    }

    /** @return static */
    public function replicate($except = null)
    {
        $instance = parent::replicate($except);
        if ($instance instanceof self && $this->resource !== '') {
            $instance->configureResource($this->resource);
        }

        return $instance;
    }

    protected function casts(): array
    {
        $casts = [
            'id' => 'integer',
            'parent_id' => 'integer',
            'progress_percent' => 'integer',
            'disaggregation_enabled' => 'boolean',
            'sensitive_data' => 'boolean',
            'hours' => 'decimal:2',
            'baseline_value' => 'decimal:4',
            'target_value' => 'decimal:4',
            'reported_value' => 'decimal:4',
            'verified_value' => 'decimal:4',
            'approved_value' => 'decimal:4',
            'min_stock_threshold' => 'decimal:3',
        ];

        foreach (config('operations.resources.'.$this->resource.'.casts', []) as $field => $cast) {
            $casts[$field] = $cast;
        }

        foreach (['planned_start', 'planned_end', 'actual_start', 'actual_end', 'planned_date',
            'training_date', 'meeting_date', 'start_date', 'end_date', 'opens_at', 'closes_at',
            'deadline', 'submitted_at', 'date_from', 'date_to', 'due_date', 'actual_start_date',
            'actual_end_date'] as $field) {
            $casts[$field] = 'date';
        }

        foreach (['scheduled_at', 'generated_at', 'published_at'] as $field) {
            $casts[$field] = 'datetime';
        }

        return $casts;
    }

    public function statusField(): string
    {
        return in_array($this->resource, ['locations', 'interventions', 'activities'], true) ? 'status' : 'status';
    }

    private function configureResource(string $resource): void
    {
        $definition = config('operations.resources.'.$resource);
        if (! is_array($definition) || ! isset($definition['table']) || ! is_array($definition['fields'] ?? null)) {
            throw new InvalidArgumentException("Unknown Phase 4 resource [{$resource}].");
        }

        $this->resource = $resource;
        $this->setTable((string) $definition['table']);
        $this->fillable = array_keys($definition['fields']);

        // Cast settings are captured when the model is constructed, before a
        // resource is bound, so resource-specific casts must be merged here.
        if ($resource === 'operational_reports') {
            $this->mergeCasts(['summary' => 'array']);
        }
    }
}
