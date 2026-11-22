/**
 * Shared presentation helpers for the Phase 5 operating layer.
 *
 * These helpers only format data already authorized by the backend. They never
 * fetch records directly and never imply institutional approval.
 */

const ROUTES = {
    'dashboard': '/dashboard',
    'activity.index': '/activity',
    'workflows.index': '/workflows',
    'workflows.instances.index': '/workflows/instances',
    'workflows.instances.show': '/workflows/instances/{instance}',
    'workflows.instances.transition': '/workflows/instances/{instance}/transition',
    'workflows.steps.transition': '/workflows/instances/{instance}/steps/{step}/transition',
    'definitions.store': '/workflows/definitions',
    'definitions.update': '/workflows/definitions/{definition}',
    'definitions.versions.store': '/workflows/definitions/{definition}/versions',
    'tasks.index': '/tasks',
    'tasks.store': '/tasks',
    'tasks.show': '/tasks/{task}',
    'tasks.transition': '/tasks/{task}/transition',
    'tasks.assign': '/tasks/{task}/assign',
    'tasks.checklist.store': '/tasks/{task}/checklist',
    'tasks.dependencies.store': '/tasks/{task}/dependencies',
    'discussions.index': '/discussions/{resourceType}/{resourceId}',
    'discussions.comments.store': '/discussions/{resourceType}/{resourceId}/comments',
    'discussions.follow.toggle': '/discussions/{resourceType}/{resourceId}/follow',
    'accountability.index': '/accountability',
    'accountability.show': '/accountability/{case}',
    'accountability.store': '/accountability',
    'accountability.transition': '/accountability/{case}/transition',
    'accountability.assign': '/accountability/{case}/assign',
    'referrals.index': '/referrals',
    'referrals.store': '/referrals',
    'referrals.transition': '/referrals/{referral}/transition',
    'notifications.index': '/notifications',
    'notifications.preferences': '/notifications/preferences',
    'notifications.preferences.update': '/notifications/preferences',
    'notifications.read-all': '/notifications/read-all',
    'notifications.read': '/notifications/{notification}/read',
    'automations.index': '/automations',
    'automations.store': '/automations',
    'automations.update': '/automations/{automation}',
    'automations.toggle': '/automations/{automation}/toggle',
    'automations.executions.retry': '/automations/executions/{execution}/retry',
    'calendar.index': '/calendar',
    'documents.index': '/document-reviews',
    'documents.store': '/document-reviews',
    'documents.assign': '/document-reviews/{review}/assign',
    'documents.transition': '/document-reviews/{review}/transition',
    'integrations.index': '/integrations',
    'integrations.store': '/integrations',
    'integrations.update': '/integrations/{integration}',
    'integrations.toggle': '/integrations/{integration}/toggle',
    'webhooks.endpoints.index': '/webhooks/endpoints',
    'webhooks.endpoints.store': '/webhooks/endpoints',
    'webhooks.deliveries.index': '/webhooks/deliveries',
    'webhooks.deliveries.retry': '/webhooks/deliveries/{delivery}/retry',
}

const DICTIONARY = {
    ar: {
        dashboard: 'لوحة الطلبات', activity: 'سجل النشاط', workflows: 'سير العمل', tasks: 'المهام',
        collaboration: 'التعاون', accountability: 'المساءلة', referrals: 'الإحالات',
        notifications: 'الإشعارات', notification_preferences: 'تفضيلات الإشعارات',
        automations: 'الأتمتة', calendar: 'التقويم', document_reviews: 'مراجعة المستندات',
        integrations: 'التكاملات', webhooks: 'الويب هوكس',
        definitions: 'التعريفات', instances: 'الحالات', steps: 'الخطوات', history: 'السجل',
        checklist: 'قائمة التحقق', dependencies: 'الاعتماديات', collaborators: 'المتعاونون',
        events: 'الأحداث', deliveries: 'عمليات التسليم', endpoints: 'نقاط الاستقبال',
        overview: 'نظرة عامة', filters: 'عوامل التصفية', actions: 'الإجراءات', summary: 'الملخص',
        settings: 'الإعدادات', channel: 'القناة', priority: 'الأولوية', category: 'الفئة',
        status: 'الحالة', reference: 'الرقم المرجعي', title: 'العنوان', owner: 'المالك',
        due_at: 'موعد الاستحقاق', start_at: 'تاريخ البدء', created_at: 'تاريخ الإنشاء',
        updated_at: 'آخر تحديث', source: 'المصدر', destination: 'الجهة', name: 'الاسم',
        key: 'المفتاح', provider: 'المزود', enabled: 'مفعّل', active: 'نشط',
        health_status: 'حالة التشغيل', metadata: 'بيانات وصفية', url: 'الرابط',
        attempts: 'المحاولات', error_code: 'رمز الخطأ', from_status: 'من', to_status: 'إلى',
        version: 'الإصدار', subject: 'الموضوع', assignment: 'الإسناد', progress: 'التقدم',
        actor: 'المُنفّذ', action: 'الإجراء', occurred_at: 'وقت الحدث', read_at: 'وقت القراءة',
        description: 'الوصف', reason: 'السبب', note: 'ملاحظة', sensitive: 'حساس',
        can_comment: 'يمكن التعليق', can_mention: 'يمكن الإشارة', can_moderate: 'يمكن الإشراف',
        followers: 'المتابعون', comments: 'التعليقات', thread: 'النقاش',
        notice: 'تنبيه', gates: 'بوابات الحوكمة', permissions: 'الصلاحيات',
        triggers: 'المحفّزات', operators: 'المعاملات', fields: 'الحقول', value: 'القيمة',
        operator: 'المعامل', conditions: 'الشروط', executions: 'عمليات التنفيذ',
        digest: 'التلخيص', quiet_hours: 'ساعات الهدوء', mandatory: 'إلزامي',
        channels: 'القنوات', unread: 'غير مقروء', all: 'الكل', mark_all_read: 'تعليم الكل كمقروء',
        submit: 'إرسال', save: 'حفظ', cancel: 'إلغاء', create: 'إنشاء', toggle: 'تبديل',
        retry: 'إعادة المحاولة', download: 'تنزيل', view: 'عرض', edit: 'تعديل',
        follow: 'متابعة', unfollow: 'إلغاء المتابعة', transition: 'تغيير الحالة', assign: 'إسناد',
        open: 'فتح', close: 'إغلاق', back: 'رجوع', choose: 'اختر', empty: 'لا توجد سجلات',
        total: 'الإجمالي', previous: 'السابق', next: 'التالي', pagination: 'التصفح',
        enabled_label: 'مفعّل', disabled_label: 'معطّل', yes: 'نعم', no: 'لا',
        configuration: 'الإعدادات', details: 'التفاصيل', audit: 'سجلات التدقيق',
        generic: 'عام', task_follow_up: 'متابعة مهمة', document_review: 'مراجعة مستند',
        data_quality: 'جودة البيانات', service_delivery: 'تقديم الخدمة',
        manual: 'يدوي', task_created: 'إنشاء مهمة', document_submitted: 'تقديم مستند',
        record_status_changed: 'تغيير حالة السجل', scheduled_review: 'مراجعة مجدولة',
        user: 'مستخدم', role: 'دور', owner: 'المالك', unassigned: 'غير مُسند',
        instant: 'فوري', daily: 'يومي', weekly: 'أسبوعي',
        task: 'مهمة', workflow: 'سير عمل', collaboration: 'تعاون', document: 'مستند',
        accountability: 'مساءلة', referral: 'إحالة', automation: 'أتمتة', integration: 'تكامل',
        system: 'النظام', meeting: 'اجتماع', workflow_step: 'خطوة سير العمل',
        communication: 'اتصالات', reporting: 'تقارير', partner_system: 'نظام شريك', utility: 'أدوات',
        draft: 'مسودة', active: 'نشط', in_progress: 'قيد التنفيذ', waiting: 'بانتظار',
        completed: 'مكتمل', rejected: 'مرفوض', cancelled: 'ملغي', suspended: 'موقوف',
        archived: 'مؤرشف', pending: 'قيد الانتظار', skipped: 'تم تخطيه',
        backlog: 'قائمة الانتظار', to_do: 'للتنفيذ', blocked: 'محجوز', under_review: 'قيد المراجعة',
        low: 'منخفضة', normal: 'عادية', high: 'عالية', urgent: 'عاجلة',
        received: 'مستلم', acknowledged: 'مُقرّ باستلامه', under_triage: 'قيد الفرز',
        assigned: 'مُسند', waiting_information: 'بانتظار معلومات', referred: 'مُحال',
        resolved: 'تم الحل', reopened: 'أُعيد فتحه', submitted: 'مُقدّم', accepted: 'مقبول',
        changes_requested: 'مطلوب تعديل', resubmitted: 'أُعيد تقديمه', reviewed: 'تمت المراجعة',
        general_feedback: 'ملاحظات عامة', service_quality: 'جودة الخدمة',
        project_feedback: 'ملاحظات المشروع', accessibility_concern: 'ملاحظة وصولية',
        staff_conduct: 'سلوك الموظفين', safeguarding_concern: 'مخاوف الحماية',
        assistance_related: 'متعلق بالمساعدة', internal: 'داخلي', website: 'الموقع الإلكتروني',
        partner: 'شريك', internal_team: 'فريق داخلي', service: 'خدمة',
        queued: 'في الانتظار', sending: 'جارٍ الإرسال', sent: 'أُرسل', delivered: 'تم التسليم',
        failed: 'فشل', unknown: 'غير معروف', disabled: 'معطّل', closed: 'مغلق',
        institutional_approvals_enabled: 'الموافقات المؤسسية',
        beneficiary_records_enabled: 'سجلات المستفيدين',
        sensitive_feedback_categories_enabled: 'الفئات الحساسة',
        complaint_public_tracking_enabled: 'التتبع العام للشكاوى',
        external_channels_enabled: 'القنوات الخارجية',
        webhooks_enabled: 'الويب هوكس',
    },
    en: {
        dashboard: 'Phase 5 dashboard', activity: 'Activity log', workflows: 'Workflows',
        tasks: 'Tasks', collaboration: 'Collaboration', accountability: 'Accountability',
        referrals: 'Referrals', notifications: 'Notifications',
        notification_preferences: 'Notification preferences', automations: 'Automations',
        calendar: 'Calendar', document_reviews: 'Document reviews', integrations: 'Integrations',
        webhooks: 'Webhooks', definitions: 'Definitions', instances: 'Instances', steps: 'Steps',
        history: 'History', checklist: 'Checklist', dependencies: 'Dependencies',
        collaborators: 'Collaborators', events: 'Events', deliveries: 'Deliveries',
        endpoints: 'Endpoints', overview: 'Overview', filters: 'Filters', actions: 'Actions',
        summary: 'Summary', settings: 'Settings', channel: 'Channel', priority: 'Priority',
        category: 'Category', status: 'Status', reference: 'Reference', title: 'Title',
        owner: 'Owner', due_at: 'Due date', start_at: 'Start date', created_at: 'Created',
        updated_at: 'Updated', source: 'Source', destination: 'Destination', name: 'Name',
        key: 'Key', provider: 'Provider', enabled: 'Enabled', active: 'Active',
        health_status: 'Health', metadata: 'Metadata', url: 'URL', attempts: 'Attempts',
        error_code: 'Error code', from_status: 'From', to_status: 'To', version: 'Version',
        subject: 'Subject', assignment: 'Assignment', progress: 'Progress', actor: 'Actor',
        action: 'Action', occurred_at: 'Occurred at', read_at: 'Read at', description: 'Description',
        reason: 'Reason', note: 'Note', sensitive: 'Sensitive', can_comment: 'Can comment',
        can_mention: 'Can mention', can_moderate: 'Can moderate', followers: 'Followers',
        comments: 'Comments', thread: 'Discussion', notice: 'Notice', gates: 'Governance gates',
        permissions: 'Permissions', triggers: 'Triggers', operators: 'Operators', fields: 'Fields',
        value: 'Value', operator: 'Operator', conditions: 'Conditions', executions: 'Executions',
        digest: 'Digest', quiet_hours: 'Quiet hours', mandatory: 'Mandatory', channels: 'Channels',
        unread: 'Unread', all: 'All', mark_all_read: 'Mark all as read', submit: 'Submit',
        save: 'Save', cancel: 'Cancel', create: 'Create', toggle: 'Toggle', retry: 'Retry',
        download: 'Download', view: 'View', edit: 'Edit', follow: 'Follow', unfollow: 'Unfollow',
        transition: 'Change status', assign: 'Assign', open: 'Open', close: 'Close', back: 'Back',
        choose: 'Choose', empty: 'No records found', total: 'Total', previous: 'Previous',
        next: 'Next', pagination: 'Pagination', enabled_label: 'Enabled', disabled_label: 'Disabled',
        yes: 'Yes', no: 'No', configuration: 'Configuration', details: 'Details',
        audit: 'Audit log', generic: 'Generic', task_follow_up: 'Task follow-up',
        document_review: 'Document review', data_quality: 'Data quality',
        service_delivery: 'Service delivery', manual: 'Manual', task_created: 'Task created',
        document_submitted: 'Document submitted', record_status_changed: 'Status changed',
        scheduled_review: 'Scheduled review', user: 'User', role: 'Role', unassigned: 'Unassigned',
        instant: 'Instant', daily: 'Daily', weekly: 'Weekly', task: 'Task', workflow: 'Workflow',
        collaboration: 'Collaboration', document: 'Document', accountability: 'Accountability',
        referral: 'Referral', automation: 'Automation', integration: 'Integration', system: 'System',
        meeting: 'Meeting', workflow_step: 'Workflow step', communication: 'Communication',
        reporting: 'Reporting', partner_system: 'Partner system', utility: 'Utility',
        draft: 'Draft', active: 'Active', in_progress: 'In progress', waiting: 'Waiting',
        completed: 'Completed', rejected: 'Rejected', cancelled: 'Cancelled', suspended: 'Suspended',
        archived: 'Archived', pending: 'Pending', skipped: 'Skipped', backlog: 'Backlog',
        to_do: 'To do', blocked: 'Blocked', under_review: 'Under review', low: 'Low',
        normal: 'Normal', high: 'High', urgent: 'Urgent', received: 'Received',
        acknowledged: 'Acknowledged', under_triage: 'Under triage', assigned: 'Assigned',
        waiting_information: 'Waiting for information', referred: 'Referred', resolved: 'Resolved',
        reopened: 'Reopened', submitted: 'Submitted', accepted: 'Accepted',
        changes_requested: 'Changes requested', resubmitted: 'Resubmitted', reviewed: 'Reviewed',
        general_feedback: 'General feedback', service_quality: 'Service quality',
        project_feedback: 'Project feedback', accessibility_concern: 'Accessibility concern',
        staff_conduct: 'Staff conduct', safeguarding_concern: 'Safeguarding concern',
        assistance_related: 'Assistance related', internal: 'Internal', website: 'Website',
        partner: 'Partner', internal_team: 'Internal team', service: 'Service',
        queued: 'Queued', sending: 'Sending', sent: 'Sent', delivered: 'Delivered', failed: 'Failed',
        unknown: 'Unknown', disabled: 'Disabled', closed: 'Closed',
        institutional_approvals_enabled: 'Institutional approvals',
        beneficiary_records_enabled: 'Beneficiary records',
        sensitive_feedback_categories_enabled: 'Sensitive feedback categories',
        complaint_public_tracking_enabled: 'Public complaint tracking',
        external_channels_enabled: 'External channels', webhooks_enabled: 'Webhooks',
    },
}

const DANGER = new Set([
    'cancelled', 'rejected', 'failed', 'overdue', 'blocked', 'error', 'closed',
    'suspended', 'ssrf_blocked', 'invalid', 'webhook_failed', 'automation_failed',
])
const WARNING = new Set([
    'waiting', 'pending', 'queued', 'sending', 'under_review', 'under_triage',
    'waiting_information', 'changes_requested', 'high', 'urgent', 'reopened',
    'resubmitted', 'to_do', 'unknown',
])
const SUCCESS = new Set([
    'completed', 'reviewed', 'accepted', 'resolved', 'active', 'enabled', 'sent',
    'delivered', 'succeeded', 'success', 'done', 'delivered', 'acknowledged',
])

export function humanize(value) {
    return String(value ?? '')
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/[_-]+/g, ' ')
        .trim()
        .replace(/\b\w/g, (character) => character.toUpperCase())
}

/** Label for a status, key or field name. Falls back to a humanized technical key. */
export function label(key, locale = 'ar', fallback = '') {
    if (key === null || key === undefined || key === '') {
        return fallback
    }

    const raw = String(key)
    const short = raw.split('.').pop()
    const active = locale === 'en' ? 'en' : 'ar'
    const dictionary = DICTIONARY[active]
    const english = DICTIONARY.en

    if (dictionary[raw] !== undefined) {
        return dictionary[raw]
    }
    if (dictionary[short] !== undefined) {
        return dictionary[short]
    }
    if (active !== 'en' && english[raw] !== undefined) {
        return english[raw]
    }
    if (active !== 'en' && english[short] !== undefined) {
        return english[short]
    }

    return fallback || humanize(short)
}

export function toneFor(value) {
    const key = String(value ?? '').toLowerCase()

    if (DANGER.has(key)) {
        return 'danger'
    }
    if (WARNING.has(key)) {
        return 'warning'
    }
    if (SUCCESS.has(key)) {
        return 'success'
    }

    return 'neutral'
}

/** Build a locale-aware Phase 5 URL without relying on Ziggy being installed. */
export function phase5Url(name, params = {}, locale = 'ar') {
    const activeLocale = locale || 'ar'

    if (typeof globalThis.route === 'function') {
        try {
            return globalThis.route(`phase5.${name}`, { locale: activeLocale, ...params })
        } catch (error) {
            // Fall through to the predictable URL layout below.
        }
    }

    let path = ROUTES[name] ?? ''
    for (const [key, value] of Object.entries(params)) {
        path = path.replace(`{${key}}`, encodeURIComponent(String(value)))
    }

    return `/${activeLocale}/operations/phase5${path}`
}

export function rowsOf(value) {
    if (Array.isArray(value)) {
        return value
    }
    if (value && Array.isArray(value.data)) {
        return value.data
    }

    return []
}

export function isPaginator(value) {
    return Boolean(value && typeof value === 'object' && Array.isArray(value.data))
}

export function asBoolean(value) {
    return value === true || value === 1 || value === '1' || value === 'true'
}

export function shortText(value, maximum = 120) {
    const text = String(value ?? '').trim()
    if (text.length <= maximum) {
        return text
    }

    return `${text.slice(0, maximum - 1)}…`
}

export function optionValue(option) {
    if (option && typeof option === 'object') {
        return String(option.value ?? '')
    }

    return String(option ?? '')
}

export function optionLabel(option, locale = 'ar') {
    if (option && typeof option === 'object') {
        return String(option.label ?? label(option.value, locale))
    }

    return label(option, locale)
}
