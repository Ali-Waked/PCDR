<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OperationalRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generates non-identifying aggregate reports from whitelisted tables.
 */
final class OperationalReportService
{
    public function __construct(
        private readonly OperationCatalog $catalog,
        private readonly OperationalRecordService $records,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{record: OperationalRecord, summary: array<string, mixed>}
     */
    public function generate(array $input): array
    {
        $category = (string) ($input['category'] ?? '');
        if (! in_array($category, $this->catalog->reportCategories(), true)) {
            throw new InvalidArgumentException('Unsupported report category.');
        }

        $sources = config('operations.report_sources.'.$category, []);
        $sources = is_array($sources) ? $sources : [];
        $dateFrom = $input['date_from'] ?? null;
        $dateTo = $input['date_to'] ?? null;

        $rows = [];
        foreach ($sources as $table) {
            if (! is_string($table) || ! $this->isAllowedTable($table)) {
                continue;
            }

            $query = DB::table($table);
            if ($dateFrom !== null && $dateFrom !== '') {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo !== null && $dateTo !== '') {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            if (Schema::hasColumn($table, 'status')) {
                $rows[] = [
                    'table' => $table,
                    'status' => '(all)',
                    'count' => (int) $query->count(),
                ];

                $query->select('status', DB::raw('count(*) as aggregate'))
                    ->groupBy('status')
                    ->orderBy('status')
                    ->each(function (object $row) use (&$rows, $table): void {
                        $rows[] = [
                            'table' => $table,
                            'status' => (string) $row->status,
                            'count' => (int) $row->aggregate,
                        ];
                    });
            } else {
                $rows[] = [
                    'table' => $table,
                    'status' => '(all)',
                    'count' => (int) $query->count(),
                ];
            }
        }

        $summary = [
            'category' => $category,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'filters' => [
                'program_id' => $input['program_id'] ?? null,
                'project_id' => $input['project_id'] ?? null,
                'location_id' => $input['location_id'] ?? null,
                'status' => $input['status_filter'] ?? null,
            ],
            'rows' => $rows,
            'total' => collect($rows)->where('status', '(all)')->sum('count'),
            'privacy' => 'Aggregate counts only. No identifiers or free text are included.',
        ];

        $record = $this->records->create('operational_reports', [
            'code' => 'RPT-'.strtoupper((string) Str::ulid()),
            'category' => $category,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'program_id' => $input['program_id'] ?? null,
            'project_id' => $input['project_id'] ?? null,
            'location_id' => $input['location_id'] ?? null,
            'status_filter' => $input['status_filter'] ?? null,
            'generated_at' => now(),
            'generated_by' => auth()->id(),
            'format' => $input['format'] ?? 'html',
            'status' => 'generated',
        ]);

        // The summary is system-generated aggregate data and is deliberately
        // not part of the HTTP-writable field whitelist.
        $record->forceFill(['summary' => $summary])->save();

        return ['record' => $record, 'summary' => $summary];
    }

    public function exportCsv(OperationalRecord $report): StreamedResponse
    {
        if ($report->getResource() !== 'operational_reports' || $report->getAttribute('format') !== 'csv') {
            throw new InvalidArgumentException('Report is not exportable as CSV.');
        }

        /** @var array<string, mixed> $summary */
        $summary = $report->getAttribute('summary') ?? [];
        $rows = is_array($summary['rows'] ?? null) ? $summary['rows'] : [];

        $callback = function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['table', 'status', 'count']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    (string) ($row['table'] ?? ''),
                    (string) ($row['status'] ?? ''),
                    (int) ($row['count'] ?? 0),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="pcdr-aggregate-report.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function isAllowedTable(string $table): bool
    {
        foreach (config('operations.report_sources', []) as $tables) {
            if (is_array($tables) && in_array($table, $tables, true)) {
                return true;
            }
        }

        return false;
    }
}
