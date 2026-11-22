<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\HomepageSection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\StrategicPillar;
use App\Services\NavigationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Structural content only.
 *
 * Pillar titles are taken verbatim from config/website.php, which transcribes
 * the approved strategic plan. Objectives are the plan's strategic objectives;
 * the English wording is a working translation, not an approved translation.
 * No people, partners, projects, results or contact details are invented.
 */
final class WebsiteSeeder extends Seeder
{
    /** @var list<string> */
    private const LOCALES = ['ar', 'en'];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedNavigation();
            $this->seedPillars();
            $this->seedHomepageSections();
        });

        app(NavigationService::class)->flush();
    }

    private function seedNavigation(): void
    {
        $items = [
            ['key' => 'home', 'ar' => 'الرئيسية', 'en' => 'Home'],
            ['key' => 'about', 'ar' => 'عن الجمعية', 'en' => 'About PCDR'],
            ['key' => 'pillars', 'ar' => 'المحاور الاستراتيجية', 'en' => 'Strategic areas'],
            ['key' => 'programs', 'ar' => 'البرامج والمشاريع', 'en' => 'Programs & projects'],
            ['key' => 'news', 'ar' => 'الأخبار والأنشطة', 'en' => 'News & activities'],
            ['key' => 'publications', 'ar' => 'التقارير والإصدارات', 'en' => 'Reports & publications'],
            ['key' => 'transparency', 'ar' => 'الشفافية والمساءلة', 'en' => 'Transparency & accountability'],
            ['key' => 'partnerships', 'ar' => 'الشراكات', 'en' => 'Partnerships'],
            ['key' => 'involve', 'ar' => 'شارك معنا', 'en' => 'Get involved'],
            ['key' => 'contact', 'ar' => 'اتصل بنا', 'en' => 'Contact'],
        ];

        foreach (['primary' => 'Primary menu', 'footer' => 'Footer menu'] as $key => $label) {
            $menu = NavigationMenu::query()->firstOrCreate(['key' => $key], ['label' => $label]);

            foreach ($items as $order => $item) {
                $record = NavigationItem::query()->updateOrCreate(
                    [
                        'navigation_menu_id' => $menu->id,
                        'link_type' => 'internal',
                        'link_value' => $item['key'],
                    ],
                    [
                        'parent_id' => null,
                        'is_enabled' => true,
                        'display_order' => $order,
                    ],
                );

                foreach (self::LOCALES as $locale) {
                    $record->translations()->updateOrCreate(
                        ['locale' => $locale],
                        ['label' => $item[$locale]],
                    );
                }
            }
        }
    }

    private function seedPillars(): void
    {
        $objectives = $this->strategicObjectives();

        foreach (config('website.pillars', []) as $order => $definition) {
            $code = (string) $definition['code'];
            $pillar = StrategicPillar::query()->updateOrCreate(
                ['code' => $code],
                [
                    'status' => ContentStatus::Published,
                    'published_at' => now(),
                    'display_order' => $order + 1,
                    'is_featured' => $order < 6,
                ],
            );

            $slug = Str::slug($code.'-'.$definition['en']);

            foreach (self::LOCALES as $locale) {
                $pillar->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'slug' => $slug,
                        'title' => $definition[$locale],
                        'short_title' => $definition[$locale],
                        'description' => $objectives[$code][$locale],
                        'objectives' => $objectives[$code][$locale],
                        'seo_title' => $definition[$locale],
                        'seo_description' => $objectives[$code][$locale],
                    ],
                );
            }
        }
    }

    private function seedHomepageSections(): void
    {
        $sections = [
            'intro' => [
                'ar' => ['eyebrow' => 'عن الجمعية', 'title' => 'تعريف مؤسسي', 'body' => config('website.vision.ar'),
                    'cta_label' => 'اقرأ المزيد', 'cta_link' => '__route:about'],
                'en' => ['eyebrow' => 'About PCDR', 'title' => 'Institutional profile', 'body' => config('website.vision.en'),
                    'cta_label' => 'Read more', 'cta_link' => '__route:about'],
            ],
            'areas' => [
                'ar' => ['eyebrow' => 'الاتجاه الاستراتيجي', 'title' => 'الاتجاه الاستراتيجي', 'body' => 'الأرقام الواردة من الخطة الاستراتيجية مستهدفات تخطيطية أولية، ولا تمثل نتائج محققة.',
                    'cta_label' => 'عرض الكل', 'cta_link' => '__route:pillars'],
                'en' => ['eyebrow' => 'Strategic direction', 'title' => 'Strategic outlook', 'body' => 'Figures from the strategic plan are initial planning targets and do not represent achieved results.',
                    'cta_label' => 'View all', 'cta_link' => '__route:pillars'],
            ],
            'framework' => [
                'ar' => ['eyebrow' => 'من الاستجابة إلى التنمية المستدامة', 'title' => 'النهج المؤسسي', 'body' => 'تنسيق الجهود الإنسانية وتعزيز الشراكات المحلية والدولية، وتقديم الاستجابات الإغاثية والتنموية المتكاملة.',
                    'cta_label' => null, 'cta_link' => null],
                'en' => ['eyebrow' => 'From response to sustainable development', 'title' => 'Institutional approach', 'body' => 'Coordinating humanitarian efforts, strengthening local and international partnerships and delivering integrated relief and development responses.',
                    'cta_label' => null, 'cta_link' => null],
            ],
            'cta' => [
                'ar' => ['eyebrow' => 'شارك معنا', 'title' => 'إبداء الاهتمام بالمشاركة', 'body' => 'تُسجل طلبات المشاركة وتراجع، ولا يُؤكد أي تكليف عبر هذا النموذج.',
                    'cta_label' => 'نموذج إبداء الاهتمام بالتطوع', 'cta_link' => '__route:involve'],
                'en' => ['eyebrow' => 'Get involved', 'title' => 'Expressions of interest', 'body' => 'Expressions of interest are recorded and reviewed. No placement is confirmed through this form.',
                    'cta_label' => 'Volunteer interest form', 'cta_link' => '__route:involve'],
            ],
        ];

        $routeMap = [
            '__route:about' => 'about',
            '__route:pillars' => 'strategic-areas',
            '__route:involve' => 'get-involved',
        ];

        foreach ($sections as $key => $translations) {
            $section = HomepageSection::query()->firstOrCreate(
                ['key' => $key],
                ['type' => $key, 'is_enabled' => true, 'display_order' => array_search($key, array_keys($sections), true)],
            );

            foreach (self::LOCALES as $locale) {
                $values = $translations[$locale];
                $link = $values['cta_link'];
                $values['cta_link'] = $link && isset($routeMap[$link])
                    ? '/'.$locale.'/'.$routeMap[$link]
                    : null;

                $section->translations()->updateOrCreate(['locale' => $locale], $values);
            }
        }
    }

    /**
     * Strategic objectives transcribed from the approved plan (chapter two).
     * English values are faithful working translations for the bilingual site.
     *
     * @return array<string, array{ar: string, en: string}>
     */
    private function strategicObjectives(): array
    {
        return [
            '01' => [
                'ar' => 'بناء منظومة استجابة إنسانية سريعة وفعالة ومنظمة للتعامل مع الأزمات والكوارث، وتلبية الاحتياجات الأساسية للأسر المتضررة.',
                'en' => 'Build a fast, effective and organised humanitarian response system for crises and disasters, and meet the basic needs of affected families.',
            ],
            '02' => [
                'ar' => 'تعزيز الأمن الغذائي للأسر الفقيرة والمتضررة وضمان وصول المساعدات الغذائية بصورة عادلة ومنتظمة.',
                'en' => 'Strengthen food security for poor and affected families and ensure fair and regular access to food assistance.',
            ],
            '03' => [
                'ar' => 'الانتقال بالأسر من الاعتماد المستمر على المساعدات إلى تعزيز القدرة على الإنتاج وتحقيق دخل مستدام.',
                'en' => 'Move families from continued dependence on assistance toward productive capacity and sustainable income.',
            ],
            '04' => [
                'ar' => 'تقديم خدمات حماية ورعاية متكاملة للفئات الأكثر هشاشة، وتعزيز التماسك الاجتماعي والقدرة على تجاوز آثار الأزمات.',
                'en' => 'Provide integrated protection and care services for the most vulnerable groups and strengthen social cohesion and the ability to overcome the effects of crises.',
            ],
            '05' => [
                'ar' => 'تحسين الظروف الصحية والبيئية للأسر والمجتمعات المتضررة والحد من المخاطر الصحية الناتجة عن الأزمات والكوارث.',
                'en' => 'Improve the health and environmental conditions of affected families and communities and reduce health risks arising from crises and disasters.',
            ],
            '06' => [
                'ar' => 'الحد من الآثار التعليمية للأزمات وتمكين الأطفال والشباب من اكتساب المهارات والمعارف اللازمة للمستقبل.',
                'en' => 'Reduce the educational effects of crises and enable children and young people to acquire the skills and knowledge needed for the future.',
            ],
            '07' => [
                'ar' => 'المساهمة في تحسين ظروف السكن والتعافي المجتمعي وتعزيز قدرة الأسر المتضررة على استعادة حياتها الطبيعية.',
                'en' => 'Contribute to improving housing conditions and community recovery and strengthen the ability of affected families to restore normal life.',
            ],
            '08' => [
                'ar' => 'بناء قاعدة مجتمعية واسعة تشارك في تحديد الاحتياجات وتنفيذ البرامج ومراقبة أثرها.',
                'en' => 'Build a broad community base that participates in identifying needs, implementing programmes and monitoring their impact.',
            ],
            '09' => [
                'ar' => 'تطوير قدرة الجمعية على الوصول إلى مصادر التمويل وتعزيز حضورها المؤسسي محلياً ودولياً.',
                'en' => "Develop the organisation's ability to access funding sources and strengthen its institutional presence locally and internationally.",
            ],
            '10' => [
                'ar' => 'تعزيز حضور الجمعية وإبراز رسالتها الإنسانية وتوثيق احتياجات المجتمع ونتائج التدخلات وتطوير التواصل مع الجمهور والشركاء.',
                'en' => "Strengthen the organisation's presence, communicate its humanitarian message, document community needs and intervention results, and develop communication with the public and partners.",
            ],
            '11' => [
                'ar' => 'بناء قرارات الجمعية وبرامجها على معلومات موثوقة واحتياجات فعلية وتعزيز قدرتها على تحليل الأزمات وتحديد التدخلات ذات الأولوية.',
                'en' => "Base the organisation's decisions and programmes on reliable information and actual needs and strengthen its ability to analyse crises and identify priority interventions.",
            ],
        ];
    }
}
