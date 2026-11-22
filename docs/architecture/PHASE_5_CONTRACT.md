# Phase 5 Implementation Contract

This document freezes the shared interface used by Phase 5 backend and frontend
modules. It is a technical implementation contract; it does not grant
institutional authority or close any open governance question.

## Shared foundation

- Configuration: `config/phase5.php` (gates, statuses, transitions, safe fields, operators, events).
- Permissions: `config/permissions.php`, module `phase5`. No `*.approve` key exists.
- Migration: `database/migrations/2026_11_25_000100_create_phase5_operating_layer_schema.php`.
- Routes: `routes/phase5.php`, mounted under `/{locale}/operations/phase5`.
- Language: `lang/ar/phase5.php`, `lang/en/phase5.php`, shared to Inertia as `phase5`.
- Authorization: `App\Services\Phase5\Phase5Authorization`.
- Support: `App\Support\Phase5Support`, `App\Events\Phase5Event`.
- Audit: existing `App\Services\AuditService` and append-only `domain_transitions`.
- Media: existing restricted media plus signed `public.operational-documents.download`.

All high-risk gates in `phase5.gates` default to `false`. Approval, beneficiary,
sensitive feedback, anonymous complaints, external delivery and webhooks remain
closed unless explicitly and formally decided outside this codebase.

## Controller contract

| Controller | Required public methods |
|---|---|
| `WorkflowController` | `index`, `storeDefinition`, `updateDefinition`, `storeVersion`, `instances`, `showInstance`, `history`, `transitionInstance`, `transitionStep` |
| `TaskController` | `index`, `store`, `show`, `update`, `destroy`, `transition`, `assign`, `storeChecklist`, `toggleChecklist`, `storeDependency` |
| `CollaborationController` | `index`, `timeline`, `storeComment`, `toggleFollow`, `updateComment`, `destroyComment` |
| `AccountabilityController` | `index`, `store`, `show`, `track`, `transition`, `assign` |
| `ReferralController` | `index`, `store`, `show`, `transition` |
| `NotificationController` | `index`, `deliveries`, `preferences`, `updatePreferences`, `markAllRead`, `markRead` |
| `AutomationController` | `index`, `executions`, `store`, `update`, `toggle`, `retry` |
| `CalendarController` | `index` |
| `DocumentReviewController` | `index`, `store`, `show`, `history`, `assign`, `transition` |
| `IntegrationController` | `index`, `store`, `update`, `toggle` |
| `WebhookController` | `endpoints`, `storeEndpoint`, `deliveries`, `retry`, `inbound` |
| `DashboardController` | `index` |
| `ActivityController` | `index` |

Controllers must be thin: Form Requests validate, services own state changes,
`Phase5Authorization` enforces resource boundaries, `AuditService` records
permission-sensitive actions, and `Phase5Event` is emitted for automation and
webhook fan-out.

## Inertia page prop contract

| Page component | Required props |
|---|---|
| `Phase5/Dashboard` | `summary`, `sections`, `activity`, `gates`, `notice` |
| `Phase5/Workflows/Index` | `definitions`, `instances`, `statuses`, `transitions`, `categories`, `permissions`, `notice` |
| `Phase5/Workflows/Show` | `instance`, `steps`, `history`, `statuses`, `stepTransitions`, `canTransition`, `notice` |
| `Phase5/Tasks/Index` | `tasks`, `statuses`, `priorities`, `views`, `permissions` |
| `Phase5/Tasks/Show` | `task`, `checklist`, `dependencies`, `collaborators`, `activity`, `statuses`, `transitions` |
| `Phase5/Collaboration/Index` | `resource`, `thread`, `comments`, `followers`, `canComment`, `canMention`, `canModerate` |
| `Phase5/Accountability/Index` | `cases`, `enabledCategories`, `disabledCategories`, `statuses`, `gates`, `notice` |
| `Phase5/Accountability/Show` | `caseData`, `events`, `statuses`, `transitions`, `canViewSensitive`, `notice` |
| `Phase5/Referrals/Index` | `referrals`, `statuses`, `transitions`, `sourceTypes`, `destinationTypes`, `notice` |
| `Phase5/Notifications/Index` | `notifications`, `unreadCount`, `categories`, `priorities` |
| `Phase5/Notifications/Preferences` | `preferences`, `categories`, `channels`, `digestOptions`, `mandatoryCategories` |
| `Phase5/Automations/Index` | `definitions`, `executions`, `triggers`, `operators`, `actions`, `fields`, `gates` |
| `Phase5/Calendar/Index` | `events`, `views`, `sources`, `timezone` |
| `Phase5/DocumentReviews/Index` | `reviews`, `statuses`, `transitions`, `permissions`, `notice` |
| `Phase5/Integrations/Index` | `integrations`, `categories`, `gates` |
| `Phase5/Webhooks/Index` | `endpoints`, `deliveries`, `events`, `gates` |
| `Phase5/Activity/Index` | `activity`, `filters` |

Collections may be arrays or Laravel paginator-backed arrays with `data`,
`current_page`, `last_page`, `per_page`, `total`. Every scalar rendered in the UI
must be safe: no secrets, no complaint narrative in notification previews, no
personal data in dashboards.

## Naming conventions

- Models: `App\Models\Phase5\*`.
- Requests: `App\Http\Requests\Phase5\*`.
- Services: `App\Services\Phase5\*`.
- Jobs: `App\Jobs\Phase5\*`.
- Listeners: `App\Listeners\Phase5\*`.
- Tests: `tests/Feature/Phase5*Test.php`.
- Vue pages: `resources/js/Pages/Phase5/**/*.vue`.
- Phase 5 components: `resources/js/Components/Phase5/*.vue`.

## Invariant rules

1. No approval permission, approver assignment, delegation, officeholder or auto-publishing.
2. No beneficiary, household, sensitive personal, medical, protection or disability data.
3. Anonymous complaint submission and public complaint tracking remain closed by default.
4. Mentions and notifications never grant or imply access to the underlying record.
5. Externally delivered messages are never claimed as delivered unless a configured provider confirms it.
6. Automation uses only structured, allow-listed fields, operators and actions; no arbitrary code.
7. Outbound webhook destinations must use HTTPS and must not resolve to private, loopback, link-local or metadata addresses.
8. Every phase-sensitive write emits an audit record with safe metadata only.
