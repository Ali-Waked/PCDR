/**
 * Central semantic map for PCDR status values.
 *
 * The application has several independent status vocabularies (CMS content,
 * inquiries, operations, Phase 5). This map deliberately contains the stored
 * values only; it never changes or normalizes backend data.
 */

const TONES = {
    green: [
        'accepted',
        'active',
        'approved_for_submission',
        'awarded',
        'completed',
        'delivered',
        'enabled',
        'established',
        'generated',
        'held',
        'published',
        'reconciled',
        'registered',
        'reported',
        'resolved',
        'responded',
        'reviewed',
        'sent',
        'verified',
    ],
    teal: [
        'acknowledged',
        'approved',
        'assigned',
        'in_discussion',
        'in_review',
        'in_triage',
        'info',
        'new',
        'ongoing',
        'open',
        'processing',
        'received',
        'referred',
        'scheduled',
        'sending',
        'submitted',
        'under_review',
        'under_triage',
    ],
    amber: [
        'backlog',
        'changes_requested',
        'contacted',
        'drafting',
        'due_soon',
        'expired',
        'identified',
        'in_progress',
        'internal_review',
        'on_hold',
        'overdue',
        'pending',
        'pending_approval',
        'pending_review',
        'planned',
        'potential',
        'proposed',
        'queued',
        'recording',
        'recorded',
        'to_do',
        'warning',
        'resubmitted',
        'under_assessment',
        'under_donor_review',
        'under_verification',
        'waiting',
        'waiting_information',
    ],
    red: [
        'blocked',
        'cancelled',
        'critical',
        'damaged',
        'dead_letter',
        'failed',
        'rejected',
        'reopened',
        'suspended',
        'terminated',
        'withdrawn',
    ],
    slate: [
        'archived',
        'closed',
        'disabled',
        'draft',
        'inactive',
        'none',
        'pruned',
        'skipped',
        'unknown',
    ],
}

const TONE_BY_VALUE = Object.fromEntries(
    Object.entries(TONES).flatMap(([tone, values]) => values.map((value) => [value, tone])),
)

/** Normalize a stored value for lookup without mutating the value itself. */
function normalizeStatus(value) {
    return String(value ?? '')
        .trim()
        .toLocaleLowerCase()
        .replace(/[\s-]+/g, '_')
}

export function statusTone(value, fallback = 'slate') {
    const tone = TONE_BY_VALUE[normalizeStatus(value)]
    return tone || fallback
}

export function withStatusTone(options) {
    return (options || []).map((option) => {
        if (option && typeof option === 'object' && !Array.isArray(option)) {
            return { ...option, tone: option.tone || statusTone(option.value ?? option.id ?? option.key) }
        }

        return { value: option, label: String(option ?? ''), tone: statusTone(option) }
    })
}

export const STATUS_TONES = TONES

export default statusTone
