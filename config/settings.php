<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Settings definitions
|--------------------------------------------------------------------------
|
| Only definitions marked "public => true" may be serialized to Inertia
| props. Credentials are never stored in the settings table; environment
| variable names below are documentation only and are never exposed.
|
*/

return [
    'definitions' => [
        'general.site_name' => [
            'group' => 'general',
            'type' => 'string',
            'public' => true,
            'default' => 'PCDR',
            'rules' => ['required', 'string', 'max:120'],
            'label' => 'Site name',
            'label_ar' => 'اسم الموقع',
        ],
        'general.tagline' => [
            'group' => 'general',
            'type' => 'string',
            'public' => true,
            'default' => 'من الاستجابة الإنسانية إلى التعافي والتنمية المستدامة',
            'rules' => ['required', 'string', 'max:255'],
            'label' => 'Tagline',
            'label_ar' => 'الشعار التعريفي',
        ],
        'branding.logo_url' => [
            'group' => 'branding',
            'type' => 'string',
            'public' => true,
            'default' => null,
            'rules' => ['nullable', 'string', 'max:2048'],
            'label' => 'Logo URL',
            'label_ar' => 'رابط الشعار',
        ],
        'localization.default_locale' => [
            'group' => 'localization',
            'type' => 'string',
            'public' => true,
            'default' => 'ar',
            'rules' => ['required', 'in:ar,en'],
            'label' => 'Default locale',
            'label_ar' => 'اللغة الافتراضية',
        ],
        'localization.available_locales' => [
            'group' => 'localization',
            'type' => 'json',
            'public' => true,
            'default' => ['ar', 'en'],
            'rules' => ['required', 'array', 'min:1'],
            'label' => 'Available locales',
            'label_ar' => 'اللغات المتاحة',
        ],
        'security.require_email_verification' => [
            'group' => 'security',
            'type' => 'boolean',
            'public' => false,
            'default' => true,
            'rules' => ['required', 'boolean'],
            'label' => 'Require email verification',
            'label_ar' => 'طلب التحقق من البريد الإلكتروني',
        ],
        'security.login_rate_limit' => [
            'group' => 'security',
            'type' => 'integer',
            'public' => false,
            'default' => 5,
            'rules' => ['required', 'integer', 'min:3', 'max:20'],
            'label' => 'Login attempts per minute',
            'label_ar' => 'محاولات الدخول في الدقيقة',
        ],
        'security.lockout_threshold' => [
            'group' => 'security',
            'type' => 'integer',
            'public' => false,
            'default' => 5,
            'rules' => ['required', 'integer', 'min:3', 'max:20'],
            'label' => 'Failed login lockout threshold',
            'label_ar' => 'حد قفل الحساب بعد محاولات فاشلة',
        ],
        'security.lockout_minutes' => [
            'group' => 'security',
            'type' => 'integer',
            'public' => false,
            'default' => 15,
            'rules' => ['required', 'integer', 'min:1', 'max:1440'],
            'label' => 'Lockout duration in minutes',
            'label_ar' => 'مدة قفل الحساب بالدقائق',
        ],
        'notifications.enabled' => [
            'group' => 'notifications',
            'type' => 'boolean',
            'public' => false,
            'default' => true,
            'rules' => ['required', 'boolean'],
            'label' => 'Enable notifications',
            'label_ar' => 'تفعيل الإشعارات',
        ],
    ],

    'group_labels' => [
        'general' => ['en' => 'General identity', 'ar' => 'الهوية العامة'],
        'branding' => ['en' => 'Branding', 'ar' => 'الهوية البصرية'],
        'localization' => ['en' => 'Localization', 'ar' => 'اللغات والإعدادات المحلية'],
        'security' => ['en' => 'Security', 'ar' => 'الأمان'],
        'notifications' => ['en' => 'Notifications', 'ar' => 'الإشعارات'],
    ],

    // These values live only in the environment or an external secret store.
    // The names are listed for operational documentation; values are never
    // read into settings, Inertia props, audit metadata, or frontend bundles.
    'secret_references' => [
        'APP_KEY',
        'DB_PASSWORD',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
    ],
];
