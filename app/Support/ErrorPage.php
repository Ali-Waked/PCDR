<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Throwable;

final class ErrorPage
{
    /**
     * @return array{
     *     locale: string,
     *     direction: string,
     *     status: int,
     *     pageTitle: string,
     *     eyebrow: string,
     *     title: string,
     *     description: string,
     *     srStatus: string,
     *     actionsLabel: string,
     *     skipLabel: string,
     *     homeLabel: string,
     *     actions: list<array{label: string, href: ?string, type: string, variant: string, icon: string}>
     * }
     */
    public static function context(Request $request, int $status): array
    {
        $locale = self::locale($request);

        return [
            'locale' => $locale,
            'direction' => Locale::direction($locale),
            'status' => $status,
            ...self::copy($status, $locale),
            'homeLabel' => self::actionLabel('home', $locale),
            'actions' => self::actions($status, $locale, self::homeUrl($locale)),
        ];
    }

    public static function locale(Request $request): string
    {
        $candidates = [
            $request->route('locale'),
            $request->segment(1),
            app()->getLocale(),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && Locale::isValid($candidate)) {
                return $candidate;
            }
        }

        $configured = (string) config('website.default_locale', 'ar');

        return Locale::isValid($configured) ? $configured : 'ar';
    }

    public static function homeUrl(string $locale): string
    {
        if (Route::has('public.home')) {
            try {
                return route('public.home', ['locale' => $locale]);
            } catch (Throwable) {
                // A named route may be unavailable while routes are being rebuilt.
            }
        }

        return url('/'.$locale);
    }

    public static function actionLabel(string $action, string $locale): string
    {
        $labels = [
            'home' => [
                'ar' => 'العودة إلى الصفحة الرئيسية',
                'en' => 'Return to homepage',
            ],
            'back' => [
                'ar' => 'العودة إلى الصفحة السابقة',
                'en' => 'Go back',
            ],
            'reload' => [
                'ar' => 'إعادة تحميل الصفحة',
                'en' => 'Reload page',
            ],
        ];

        return $labels[$action][$locale] ?? $labels[$action]['ar'];
    }

    /**
     * @return array{
     *     pageTitle: string,
     *     eyebrow: string,
     *     title: string,
     *     description: string,
     *     srStatus: string,
     *     actionsLabel: string,
     *     skipLabel: string
     * }
     */
    private static function copy(int $status, string $locale): array
    {
        return match ($status) {
            403 => [
                'pageTitle' => $locale === 'ar' ? 'الوصول مقيّد | PCDR' : 'Access restricted | PCDR',
                'eyebrow' => $locale === 'ar' ? 'HTTP 403 · الوصول مقيّد' : 'HTTP 403 · Access restricted',
                'title' => $locale === 'ar'
                    ? 'ليس لديك صلاحية للوصول إلى هذه الصفحة'
                    : "You don't have permission to access this page.",
                'description' => $locale === 'ar'
                    ? 'عذراً، لا تملك الصلاحيات اللازمة للوصول إلى هذا المحتوى. إذا كنت تعتقد أن هذا غير صحيح، يرجى التواصل مع المسؤول المختص عبر قنوات التواصل الرسمية.'
                    : 'Sorry, you do not have the required permissions to access this content. If you believe this is an error, please contact the appropriate administrator through the official channels.',
                'srStatus' => $locale === 'ar'
                    ? 'حالة الخطأ: 403، الوصول مقيّد.'
                    : 'Error status: 403, access restricted.',
                'actionsLabel' => $locale === 'ar' ? 'خيارات التنقل' : 'Navigation options',
                'skipLabel' => $locale === 'ar' ? 'تخطَّ إلى المحتوى الرئيسي' : 'Skip to main content',
            ],
            404 => [
                'pageTitle' => $locale === 'ar' ? 'الصفحة غير موجودة | PCDR' : 'Page not found | PCDR',
                'eyebrow' => $locale === 'ar' ? 'HTTP 404 · الصفحة غير موجودة' : 'HTTP 404 · Page unavailable',
                'title' => $locale === 'ar'
                    ? 'عذراً، لم نتمكن من العثور على هذه الصفحة'
                    : "We couldn't find this page.",
                'description' => $locale === 'ar'
                    ? 'قد يكون الرابط غير صحيح، أو أن الصفحة لم تعد متاحة. يمكنك العودة إلى الصفحة الرئيسية ومتابعة استكشاف موقعنا.'
                    : 'The link may be incorrect, or the page may no longer be available. You can return to the homepage and continue exploring our website.',
                'srStatus' => $locale === 'ar'
                    ? 'حالة الخطأ: 404، الصفحة غير موجودة.'
                    : 'Error status: 404, page not found.',
                'actionsLabel' => $locale === 'ar' ? 'خيارات التنقل' : 'Navigation options',
                'skipLabel' => $locale === 'ar' ? 'تخطَّ إلى المحتوى الرئيسي' : 'Skip to main content',
            ],
            default => [
                'pageTitle' => $locale === 'ar' ? 'حدث خطأ غير متوقع | PCDR' : 'Something went wrong | PCDR',
                'eyebrow' => $locale === 'ar' ? 'HTTP 500 · خطأ تقني' : 'HTTP 500 · Technical problem',
                'title' => $locale === 'ar' ? 'حدث خطأ غير متوقع' : 'Something went wrong.',
                'description' => $locale === 'ar'
                    ? 'نواجه مشكلة تقنية أثناء معالجة طلبك. نعتذر عن هذا الإزعاج. يرجى المحاولة مرة أخرى بعد قليل.'
                    : 'We encountered a technical problem while processing your request. We apologize for the inconvenience. Please try again shortly.',
                'srStatus' => $locale === 'ar'
                    ? 'حالة الخطأ: 500، حدث خطأ غير متوقع.'
                    : 'Error status: 500, something went wrong.',
                'actionsLabel' => $locale === 'ar' ? 'خيارات التنقل' : 'Navigation options',
                'skipLabel' => $locale === 'ar' ? 'تخطَّ إلى المحتوى الرئيسي' : 'Skip to main content',
            ],
        };
    }

    /**
     * @return list<array{label: string, href: ?string, type: string, variant: string, icon: string}>
     */
    private static function actions(int $status, string $locale, string $homeUrl): array
    {
        $home = [
            'label' => self::actionLabel('home', $locale),
            'href' => $homeUrl,
            'type' => 'link',
            'variant' => 'primary',
            'icon' => 'home',
        ];

        $secondary = $status === 500
            ? [
                'label' => self::actionLabel('reload', $locale),
                'href' => null,
                'type' => 'reload',
                'variant' => 'secondary',
                'icon' => 'reload',
            ]
            : [
                'label' => self::actionLabel('back', $locale),
                'href' => null,
                'type' => 'back',
                'variant' => 'secondary',
                'icon' => 'back',
            ];

        return [$home, $secondary];
    }
}
