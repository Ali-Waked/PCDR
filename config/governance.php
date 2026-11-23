<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Governance boundary
|--------------------------------------------------------------------------
|
| Platform Owner is the complete TECHNICAL authority for every platform
| module. Institutional approval, appointment, delegation, signing and
| ratification authority are deliberately outside that boundary and require
| separate formal authorization. These patterns fail closed for future keys.
|
*/

return [
    'authority' => [
        'permission_prefixes' => [
            'institutional.',
            'governance.',
            'authority.',
        ],
        'permission_suffixes' => [
            '.approve',
            '.approval',
            '.approve_request',
            '.reject',
            '.rejection',
            '.appoint',
            '.appointment',
            '.delegate',
            '.delegation',
            '.signoff',
            '.signature',
            '.ratify',
            '.ratification',
            '.authorize',
            '.authorization',
            '.nominate',
            '.nomination',
        ],
        'abilities' => [
            'approve',
            'approval',
            'reject',
            'rejection',
            'appoint',
            'appointment',
            'delegate',
            'delegation',
            'signoff',
            'signature',
            'ratify',
            'ratification',
            'authorize',
            'authorization',
            'nominate',
            'nomination',
            'institutionalauthority',
            'governanceauthority',
        ],
        // Stems catch inflected and compound future keys that exact suffixes
        // miss (for example "content.approvals" or "signOffRequest").
        'term_stems' => [
            'approv',
            'reject',
            'appoint',
            'delegat',
            'signoff',
            'signature',
            'ratif',
            'authoriz',
            'nominat',
            'institutional',
            'governance',
            'authority',
        ],
        'reason' => [
            'ar' => 'صلاحية الموافقة المؤسسية ليست جزءًا من الصلاحية التقنية وتتطلب تفويضًا رسميًا منفصلًا.',
            'en' => 'Institutional approval authority is outside technical control and requires separate formal authorization.',
        ],
    ],

    'owner_bootstrap' => [
        'email' => env('PCDR_OWNER_EMAIL'),
        'name' => env('PCDR_OWNER_NAME'),
        'password' => env('PCDR_OWNER_PASSWORD'),
    ],
];
