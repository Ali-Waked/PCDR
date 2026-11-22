<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Actions\UploadMedia;
use App\Exceptions\InvalidDomainTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\AttachOperationalDocumentRequest;
use App\Http\Requests\Operations\GenerateReportRequest;
use App\Http\Requests\Operations\InventoryMovementRequest;
use App\Http\Requests\Operations\OperationIndexRequest;
use App\Http\Requests\Operations\ReconcileDistributionRequest;
use App\Http\Requests\Operations\StoreOperationalRecordRequest;
use App\Http\Requests\Operations\SubmitProposalRequest;
use App\Http\Requests\Operations\TransitionOperationalRecordRequest;
use App\Http\Requests\Operations\UpdateOperationalRecordRequest;
use App\Http\Requests\Operations\VerifyMeasurementRequest;
use App\Models\DocumentLink;
use App\Models\OperationalRecord;
use App\Services\CmsAccess;
use App\Services\InventoryLedgerService;
use App\Services\OperationalDashboardService;
use App\Services\OperationalDocumentService;
use App\Services\OperationalQueryService;
use App\Services\OperationalRecordService;
use App\Services\OperationalReportService;
use App\Services\OperationalTransitionService;
use App\Services\OperationalWorkflowService;
use App\Services\OperationCatalog;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OperationController extends Controller
{
    public function __construct(
        private readonly OperationCatalog $catalog,
        private readonly OperationalQueryService $query,
        private readonly OperationalRecordService $records,
        private readonly OperationalTransitionService $transitions,
        private readonly OperationalWorkflowService $workflows,
        private readonly OperationalDocumentService $documents,
        private readonly InventoryLedgerService $ledger,
        private readonly OperationalReportService $reports,
        private readonly OperationalDashboardService $dashboard,
        private readonly CmsAccess $access,
    ) {}

    public function index(string $locale, OperationIndexRequest $request): Response
    {
        $resource = $this->routeResource();
        $paginator = $this->query->paginate($resource, $request->queryParameters());

        return Inertia::render('Operations/ResourceIndex', [
            'resource' => $resource,
            'definition' => $this->catalog->definition($resource),
            'records' => $paginator->through(fn (OperationalRecord $record): array => $this->query->serialize($record))->items(),
            'pagination' => $this->query->paginationPayload($paginator),
            'fields' => $this->catalog->fields($resource),
            'statuses' => $this->catalog->statuses($resource),
            'filters' => $this->catalog->filterFields($resource),
            'permissions' => $this->resourcePermissions($resource),
            'notices' => $this->catalog->isSensitive($resource)
                ? [__('operations.sensitive_notice')]
                : [],
        ]);
    }

    public function create(string $locale): Response
    {
        $resource = $this->routeResource();

        return Inertia::render('Operations/ResourceForm', [
            'mode' => 'create',
            'resource' => $resource,
            'definition' => $this->catalog->definition($resource),
            'record' => null,
            'fields' => $this->catalog->fields($resource),
            'validationRules' => $this->catalog->validationRules($resource),
            'statuses' => $this->catalog->statuses($resource),
            'transitions' => $this->catalog->transitions($resource),
            'locales' => config('operations.locales', ['ar', 'en']),
            'translationFields' => $this->catalog->translationFields($resource),
            'documents' => [],
            'transitionsHistory' => [],
            'permissions' => $this->resourcePermissions($resource),
        ]);
    }

    public function store(string $locale, StoreOperationalRecordRequest $request): RedirectResponse
    {
        $resource = $this->routeResource();
        $record = $this->records->create(
            $resource,
            $request->validatedAttributes(),
            $request->validatedTranslations(),
        );

        return $this->editRedirect($resource, (int) $record->getKey(), __('operations.record_created'));
    }

    public function edit(string $locale, int $record): Response
    {
        $resource = $this->routeResource();
        $recordModel = $this->resolveRecord($resource, $record);
        $canDownload = $this->allows('documents.download');

        return Inertia::render('Operations/ResourceForm', [
            'mode' => 'edit',
            'resource' => $resource,
            'definition' => $this->catalog->definition($resource),
            'record' => $this->query->serialize($recordModel),
            'fields' => $this->catalog->fields($resource),
            'validationRules' => $this->catalog->validationRules($resource),
            'statuses' => $this->catalog->statuses($resource),
            'transitions' => $this->catalog->transitions($resource),
            'locales' => config('operations.locales', ['ar', 'en']),
            'translationFields' => $this->catalog->translationFields($resource),
            'documents' => array_map(
                fn (array $document): array => $document + [
                    'signedUrl' => $canDownload && isset($document['id'])
                        ? $this->documents->signedDownloadUrl(
                            DocumentLink::query()->findOrFail((int) $document['id']),
                            $locale,
                        )
                        : null,
                ],
                $this->documents->serializeLinks($resource, $record),
            ),
            'transitionsHistory' => $recordModel->domainTransitions()
                ->with('actor')
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn ($transition): array => [
                    'id' => $transition->getKey(),
                    'from' => $transition->from_status,
                    'to' => $transition->to_status,
                    'reason' => $transition->reason,
                    'actor' => $transition->actor?->name,
                    'created_at' => $transition->created_at?->toISOString(),
                ])->all(),
            'permissions' => $this->resourcePermissions($resource),
        ]);
    }

    public function update(string $locale, UpdateOperationalRecordRequest $request, int $record): RedirectResponse
    {
        $resource = $this->routeResource();
        $recordModel = $this->resolveRecord($resource, $record);
        $this->records->update($resource, $recordModel, $request->validatedAttributes(), $request->validatedTranslations());

        return $this->editRedirect($resource, $record, __('operations.record_updated'));
    }

    public function destroy(string $locale, int $record): RedirectResponse
    {
        $resource = $this->routeResource();
        $this->records->delete($resource, $this->resolveRecord($resource, $record));

        return redirect()->route("operations.{$resource}.index", [
            'locale' => $locale,
        ])->with('success', __('operations.record_deleted'));
    }

    public function transition(string $locale, TransitionOperationalRecordRequest $request, int $record): RedirectResponse
    {
        $resource = $this->routeResource();
        $input = $request->transitionInput();

        try {
            $this->transitions->transition(
                $resource,
                $this->resolveRecord($resource, $record),
                (string) $input['to_status'],
                $input,
            );
        } catch (InvalidDomainTransition|InvalidArgumentException $exception) {
            return back()->withErrors(['to_status' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', __('operations.status_transitioned'));
    }

    public function verifyMeasurement(string $locale, VerifyMeasurementRequest $request, int $record): RedirectResponse
    {
        try {
            $this->workflows->verifyMeasurement(
                $this->resolveRecord('indicator_measurements', $record),
                $request->verificationInput(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['verified_value' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', __('operations.measurement_verified'));
    }

    public function submitProposal(string $locale, SubmitProposalRequest $request, int $record): RedirectResponse
    {
        try {
            $this->workflows->submitProposal(
                $this->resolveRecord('grant_proposals', $record),
                $request->submissionInput(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['evidence_media_id' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', __('operations.proposal_submitted'));
    }

    public function reconcileDistribution(string $locale, ReconcileDistributionRequest $request, int $record): RedirectResponse
    {
        try {
            $this->workflows->reconcileDistribution(
                $this->resolveRecord('distribution_records', $record),
                $request->reconciliationInput(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['verified_quantity' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', __('operations.distribution_reconciled'));
    }

    public function attachDocument(
        string $locale,
        AttachOperationalDocumentRequest $request,
        UploadMedia $upload,
        int $record,
    ): RedirectResponse {
        $resource = $this->routeResource();
        $this->resolveRecord($resource, $record);

        try {
            $media = $upload->handle($request->file('document'), true, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['document' => $exception->getMessage()]);
        }

        $this->documents->attach(
            $resource,
            $record,
            $media,
            (string) ($request->input('usage') ?: 'document'),
            auth()->id(),
        );

        return back()->with('success', __('operations.document_attached'));
    }

    public function inventory(string $locale): Response
    {
        return Inertia::render('Operations/Inventory', [
            'resource' => 'warehouses',
            'warehouses' => $this->catalog->record('warehouses')->newQuery()
                ->orderBy('code')->get(['id', 'code'])->all(),
            'items' => $this->catalog->record('inventory_items')->newQuery()
                ->orderBy('code')->get(['id', 'code', 'sku', 'unit'])->all(),
            'balances' => $this->ledger->balances(),
            'movements' => $this->ledger->movements(),
            'movementTypes' => $this->catalog->inventoryMovementTypes(),
            'valuationEnabled' => $this->catalog->gate('inventory_valuation_enabled'),
            'permissions' => [
                'receive' => $this->allows('inventory.receive'),
                'issue' => $this->allows('inventory.issue'),
                'transfer' => $this->allows('inventory.transfer'),
                'adjust' => $this->allows('inventory.adjust'),
                'reverse' => $this->allows('inventory.reverse'),
            ],
        ]);
    }

    public function storeMovement(string $locale, InventoryMovementRequest $request): RedirectResponse
    {
        try {
            $movements = $this->ledger->post($request->movementInput());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()])->withInput();
        }

        $first = $movements[0] ?? null;

        return back()->with('success', __('operations.movement_posted', [
            'code' => $first?->code ?? (string) $first?->getKey(),
        ]));
    }

    public function reverseMovement(string $locale, int $movement): RedirectResponse
    {
        try {
            $this->ledger->reverse($movement, auth()->id(), 'Correction reversal');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['reversal' => $exception->getMessage()]);
        }

        return back()->with('success', __('operations.movement_reversed'));
    }

    public function reports(string $locale): Response
    {
        return Inertia::render('Operations/Reports', [
            'categories' => $this->catalog->reportCategories(),
            'sources' => config('operations.report_sources', []),
            'reports' => $this->catalog->record('operational_reports')->newQuery()
                ->orderByDesc('id')->limit(50)->get()
                ->map(fn (OperationalRecord $record): array => [
                    'id' => $record->getKey(),
                    'code' => $record->getAttribute('code'),
                    'category' => $record->getAttribute('category'),
                    'format' => $record->getAttribute('format'),
                    'generated_at' => $record->generated_at?->toISOString(),
                    'exportUrl' => $record->getAttribute('format') === 'csv' && $this->allows('reports.export')
                        ? route('operations.reports.export', [
                            'locale' => $locale,
                            'report' => $record->getKey(),
                        ])
                        : null,
                ])->all(),
            'generated' => null,
            'canCreate' => $this->allows('reports.create'),
            'canExport' => $this->allows('reports.export'),
        ]);
    }

    public function generateReport(string $locale, GenerateReportRequest $request): RedirectResponse
    {
        $result = $this->reports->generate($request->reportInput());

        return back()->with('success', __('operations.report_generated', [
            'code' => (string) $result['record']->getAttribute('code'),
        ]));
    }

    public function exportReport(string $locale, int $report): StreamedResponse
    {
        abort_unless($this->allows('reports.export'), 403);

        return $this->reports->exportCsv($this->resolveRecord('operational_reports', $report));
    }

    public function dashboard(string $locale): Response
    {
        return Inertia::render('Operations/Dashboard', [
            'summary' => $this->dashboard->summary(),
            'canManage' => $this->allows('reports.view'),
        ]);
    }

    private function routeResource(): string
    {
        $routeName = request()->route()?->getName() ?? '';
        $resource = explode('.', $routeName)[1] ?? '';
        $this->assertResource($resource);

        return $resource;
    }

    private function assertResource(string $resource): void
    {
        if (! $this->catalog->has($resource)) {
            abort(404);
        }
    }

    private function resolveRecord(string $resource, int $id): OperationalRecord
    {
        $this->assertResource($resource);

        /** @var OperationalRecord $record */
        $record = $this->catalog->record($resource)->newQuery()->whereKey($id)->firstOrFail();

        return $record;
    }

    private function editRedirect(string $resource, int $record, string $message): RedirectResponse
    {
        return redirect()->route("operations.{$resource}.edit", [
            'locale' => request()->route('locale'),
            'record' => $record,
        ])->with('success', $message);
    }

    /** @return array<string, bool> */
    private function resourcePermissions(string $resource): array
    {
        $permissions = [];
        foreach (['view', 'create', 'update', 'transition'] as $action) {
            $permissions[$action] = $this->allows($this->catalog->permission($resource, $action));
        }

        return $permissions;
    }

    private function allows(string $permission): bool
    {
        $user = auth()->user();

        return $user !== null && $this->access->allows($user, $permission);
    }
}
