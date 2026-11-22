<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import SplitText from '../../Components/StaticHome/SplitText.vue'
import LanguageSwitcher from '../../Components/LanguageSwitcher.vue'
import { useI18n } from '../../composables/useI18n'
import '../../../css/pcdr-home.css'

const props = defineProps({
    meta: { type: Object, default: null },
})

const root = ref(null)
const intro = ref(null)
const header = ref(null)
const heroArt = ref(null)
const navOpen = ref(false)

watch(navOpen, (open) => {
    if (typeof document !== 'undefined') {
        document.body.classList.toggle('pcdr-menu-open', open)
    }
})

const content = {
    ar: {
        skip: 'تخطَّ إلى المحتوى',
        navAria: 'القائمة الرئيسية',
        langShort: 'EN',
        langLabel: 'Switch to English',
        langRowLabel: 'اللغة',
        partner: 'كن شريكاً',
        logoAlt: 'شعار جمعية العمل التشاركي في الأزمات والكوارث – قطاع غزة',
        nav: [
            { label: 'الرئيسية', href: '#top' },
            { label: 'من نحن', href: '#about' },
            { label: 'مجالات عملنا', href: '#areas' },
            { label: 'برامجنا', href: '#programs' },
            { label: 'الأثر', href: '#targets' },
            { label: 'المعرفة والإعلام', href: '#knowledge' },
            { label: 'تواصل معنا', href: '#contact' },
        ],
        hero: {
            eyebrow: 'العمل التشاركي · قطاع غزة',
            title: 'من الاستجابة الإنسانية\nإلى التعافي والتنمية المستدامة',
            lead: 'نعمل على تنسيق الجهود الإنسانية وتعزيز المشاركة المجتمعية، من أجل استجابة أكثر فاعلية وتعافٍ أكثر استدامة، يحفظ كرامة الإنسان ويعزز قدرة المجتمعات على الصمود.',
            discover: 'تعرّف على عملنا',
            partner: 'كن شريكاً في الأثر',
        },
        about: {
            eyebrow: 'من نحن',
            title: 'الإنسان أولاً... والمجتمع شريك في التغيير',
            visionTitle: 'الرؤية',
            vision: 'الريادة في العمل الإنساني والتنموي التشاركي، والمساهمة في بناء مجتمع فلسطيني أكثر قدرة على الصمود والتعافي، يتمتع فيه الأفراد والأسر بالكرامة والعدالة وتكافؤ الفرص.',
            missionTitle: 'الرسالة',
            mission: 'تنسيق الجهود الإنسانية وتعزيز الشراكات المحلية والدولية، وتقديم الاستجابات الإغاثية والتنموية المتكاملة، وتمكين الفئات الأكثر هشاشة، وتحسين الظروف المعيشية للأسر المتضررة والفقيرة، من خلال برامج مهنية ومستدامة تقوم على المشاركة المجتمعية والشفافية والمساءلة.',
        },
        areas: {
            eyebrow: '١١ محوراً استراتيجياً',
            title: 'مجالات عملنا',
            pillars: [
                { title: 'الاستجابة الإنسانية وإدارة الأزمات والكوارث', body: 'منظومة استجابة سريعة ومنظمة لتلبية الاحتياجات الأساسية للأسر المتضررة.' },
                { title: 'الأمن الغذائي وتحسين الظروف المعيشية', body: 'وصول عادل ومنتظم للمساعدات الغذائية، مع دعم الزراعة المنزلية.' },
                { title: 'التمكين الاقتصادي وسبل العيش', body: 'الانتقال بالأسر من الاعتماد على المساعدات إلى دخل مستدام.' },
                { title: 'الحماية الاجتماعية والرعاية النفسية والاجتماعية', body: 'رعاية متكاملة للأيتام والأشخاص ذوي الإعاقة والنساء والأطفال وكبار السن.' },
                { title: 'الصحة العامة والمياه والإصحاح البيئي', body: 'الحد من المخاطر الصحية وتحسين خدمات المياه والنظافة.' },
                { title: 'التعليم وتنمية قدرات الأطفال والشباب', body: 'الحد من الفاقد التعليمي وبناء مهارات المستقبل.' },
                { title: 'التعافي المبكر وتحسين السكن وإعادة تأهيل المرافق', body: 'تحسين ظروف السكن وإعادة تأهيل المرافق المجتمعية الصغيرة.' },
                { title: 'المشاركة المجتمعية والتطوع والعمل التشاركي', body: 'لجان وفرق تطوعية تشارك في تحديد الأولويات ومتابعة النتائج.' },
                { title: 'الشراكات المحلية والدولية واستقطاب التمويل', body: 'بناء شراكات محلية ودولية وتنويع مصادر التمويل.' },
                { title: 'الإعلام والاتصال والمناصرة', body: 'توثيق الاحتياجات وإبراز الرسالة الإنسانية بشفافية.' },
                { title: 'البحث وتقييم الاحتياجات وقياس الأثر', body: 'قرارات مبنية على مسوح ودراسات وتقييم موثق للأثر.' },
            ],
        },
        approach: {
            eyebrow: 'منهجنا الإنساني',
            title: 'رحلة متصلة وليست تدخلاً عابراً',
            steps: [
                { title: 'الاستجابة الطارئة', body: 'تلبية الاحتياجات العاجلة بسرعة وتنظيم.' },
                { title: 'التعافي المبكر', body: 'استعادة مصادر الرزق والسكن والخدمات الأساسية.' },
                { title: 'الصمود', body: 'تعزيز قدرة الأسر والمجتمعات على مواجهة الأزمات.' },
                { title: 'التنمية المستدامة', body: 'مجتمعات منتجة ومعتمدة على ذاتها تصنع مستقبلها.' },
            ],
        },
        programs: {
            eyebrow: 'مجالات البرامج المقترحة',
            title: 'البرامج والتدخلات',
            note: 'مجالات برامجية ضمن الخطة الاستراتيجية 2027–2029، وليست تدخلات منفذة.',
            tiles: [
                'الاستجابة للطوارئ', 'الأمن الغذائي', 'سبل العيش والتمكين',
                'الحماية والدعم النفسي والاجتماعي', 'الصحة والمياه والإصحاح',
                'التعليم والشباب', 'التعافي المبكر والإيواء', 'المشاركة المجتمعية',
            ],
        },
        targets: {
            badge: 'مستهدفات الخطة الاستراتيجية 2027',
            title: 'ما نستهدف الوصول إليه بنهاية 2027',
            note: 'أرقام تخطيطية تخضع للتمويل والقدرات المتاحة، وهي ليست نتائج محققة.',
            stats: [
                { value: 3000, display: '3,000', label: 'الأسر المستهدفة بالتدخلات الإنسانية' },
                { value: 1500, display: '1,500', label: 'الأسر المستهدفة في الأمن الغذائي' },
                { value: 200, display: '200', label: 'الشباب المستهدف تدريبهم' },
                { value: 100, display: '100', label: 'المتطوعون المستهدف إشراكهم' },
                { value: 50, display: '50', label: 'المشاريع الصغيرة المستهدفة' },
            ],
        },
        community: {
            eyebrow: 'المنهج التشاركي',
            title: 'التغيير المستدام يبدأ من المجتمع',
            body: 'يشارك المجتمع في تحديد الاحتياجات وتصميم التدخلات ومتابعة تنفيذها وتقييم أثرها، بما يعزز الشفافية والعدالة ويحد من الازدواجية.',
            roles: [
                { title: 'اللجان المجتمعية', body: 'تحديد الأولويات في الأحياء ومراكز الإيواء.' },
                { title: 'المتطوعون', body: 'إسناد ميداني وفق مدونة سلوك.' },
                { title: 'المبادرات المحلية', body: 'مبادرات شبابية ونسوية نطمح إلى دعمها.' },
                { title: 'الشركاء المؤسسيون', body: 'موارد وخبرات ضمن اتفاقيات واضحة.' },
            ],
        },
        partners: {
            eyebrow: 'الشراكات',
            title: 'معاً نحو استجابة إنسانية أكثر تكاملاً',
            lead: 'نطمح إلى بناء شراكات فاعلة. الفئات التالية مجالات تعاون مخططة وليست اتفاقيات قائمة.',
            categories: [
                'المؤسسات الحكومية والمحلية', 'المنظمات الدولية والعربية',
                'الجامعات والمراكز البحثية', 'القطاع الخاص والمجتمع المدني',
            ],
        },
        knowledge: {
            eyebrow: 'المعرفة والإعلام',
            title: 'تقارير ودراسات وقصص',
            note: 'محتوى توضيحي فقط — ليس أخباراً أو أنشطة فعلية للجمعية.',
            articles: [
                { tag: 'توضيحي · أخبار وتقارير', title: 'عنوان تقرير توضيحي' },
                { tag: 'توضيحي · دراسات واحتياجات', title: 'عنوان دراسة احتياجات توضيحية' },
                { tag: 'توضيحي · قصص مجتمعية', title: 'قصة مجتمعية توضيحية' },
            ],
        },
        cta: {
            title: 'لأن التعافي مسؤولية مشتركة',
            lead: 'نؤمن أن العمل الإنساني الأكثر أثراً يبدأ بالتعاون، ويستمر بالمشاركة، ويصنع مستقبلاً أكثر قدرة على الصمود.',
            contact: 'تواصل معنا',
            volunteer: 'كن متطوعاً',
            explore: 'استكشف مجالات عملنا',
        },
        footer: {
            description: 'جمعية العمل التشاركي في الأزمات والكوارث الخيرية – قطاع غزة. نموذج أولي للهوية الرقمية؛ المحتوى مستمد من الخطة الاستراتيجية 2027–2029.',
            contactTitle: 'التواصل',
            contactBody: 'بيانات التواصل والعنوان وحسابات التواصل الاجتماعي ستُضاف بعد اعتمادها.',
        },
    },
    en: {
        skip: 'Skip to content',
        navAria: 'Main navigation',
        langShort: 'ع',
        langLabel: 'التبديل إلى العربية',
        langRowLabel: 'Language',
        partner: 'Partner with us',
        logoAlt: 'PCDR logo – Participatory Action in Crises and Disasters – Gaza Strip',
        nav: [
            { label: 'Home', href: '#top' },
            { label: 'About', href: '#about' },
            { label: 'Our Work', href: '#areas' },
            { label: 'Programs', href: '#programs' },
            { label: 'Impact', href: '#targets' },
            { label: 'Knowledge & Media', href: '#knowledge' },
            { label: 'Contact', href: '#contact' },
        ],
        hero: {
            eyebrow: 'Participatory action · Gaza',
            title: 'From Humanitarian Response\nto Recovery and Sustainable Development',
            lead: 'We coordinate humanitarian efforts and strengthen community participation for a more effective response and a more lasting recovery — one that protects human dignity and builds the resilience of communities.',
            discover: 'Discover our work',
            partner: 'Be a partner in impact',
        },
        about: {
            eyebrow: 'Who we are',
            title: 'People First — and Communities as Partners in Change',
            visionTitle: 'Vision',
            vision: 'Leadership in participatory humanitarian and development action, contributing to a more resilient Palestinian society where individuals and families enjoy dignity, justice and equal opportunity.',
            missionTitle: 'Mission',
            mission: 'Coordinating humanitarian efforts and strengthening local and international partnerships; delivering integrated relief and development responses; empowering the most vulnerable; and improving living conditions for affected and poor families through professional, sustainable programs grounded in community participation, transparency and accountability.',
        },
        areas: {
            eyebrow: '11 strategic pillars',
            title: 'Our Areas of Work',
            pillars: [
                { title: 'Humanitarian response and crisis & disaster management', body: 'A rapid, organized response system that meets the essential needs of affected families.' },
                { title: 'Food security and living conditions', body: 'Fair and regular access to food assistance, with support for home-based food production.' },
                { title: 'Economic empowerment and livelihoods', body: 'Moving families from continued reliance on aid toward sustainable income.' },
                { title: 'Social protection and psychosocial care', body: 'Integrated care for orphans, persons with disabilities, women, children and older people.' },
                { title: 'Public health, water and sanitation', body: 'Reducing health risks and improving water, hygiene and sanitation services.' },
                { title: 'Education and youth capacity building', body: 'Limiting learning loss and building the skills of the future.' },
                { title: 'Early recovery, housing and facilities', body: 'Improving housing conditions and rehabilitating small community facilities.' },
                { title: 'Community participation and volunteering', body: 'Committees and volunteer teams that help set priorities and follow up on results.' },
                { title: 'Partnerships and resource mobilization', body: 'Building local and international partnerships and diversifying funding sources.' },
                { title: 'Media, communication and advocacy', body: 'Documenting needs and presenting our humanitarian message with transparency.' },
                { title: 'Research, needs assessment and impact measurement', body: 'Decisions grounded in surveys, studies and documented evaluation of impact.' },
            ],
        },
        approach: {
            eyebrow: 'Our humanitarian approach',
            title: 'A continuous journey, not a one-off intervention',
            steps: [
                { title: 'Emergency response', body: 'Meeting urgent needs quickly and in an organized way.' },
                { title: 'Early recovery', body: 'Restoring livelihoods, shelter and basic services.' },
                { title: 'Resilience', body: 'Strengthening the ability of families and communities to face crises.' },
                { title: 'Sustainable development', body: 'Productive, self-reliant communities that shape their own future.' },
            ],
        },
        programs: {
            eyebrow: 'Proposed program areas',
            title: 'Programs and Interventions',
            note: 'Strategic program areas under the 2027–2029 plan — not completed interventions.',
            tiles: [
                'Emergency response', 'Food security', 'Livelihoods',
                'Protection and psychosocial support', 'Health, water and sanitation',
                'Education and youth', 'Early recovery and shelter', 'Community participation',
            ],
        },
        targets: {
            badge: 'Strategic Plan Targets 2027',
            title: 'Where we aim to be by the end of 2027',
            note: 'Planned targets, subject to funding and capacity. They are not achieved results.',
            stats: [
                { value: 3000, display: '3,000', label: 'Families targeted for humanitarian interventions' },
                { value: 1500, display: '1,500', label: 'Families targeted for food security support' },
                { value: 200, display: '200', label: 'Young people targeted for training' },
                { value: 100, display: '100', label: 'Volunteers targeted for engagement' },
                { value: 50, display: '50', label: 'Small projects targeted' },
            ],
        },
        community: {
            eyebrow: 'Participatory approach',
            title: 'Lasting change starts with the community',
            body: 'Communities help identify needs, design interventions, follow their implementation and evaluate their impact — which strengthens transparency and fairness and avoids duplication.',
            roles: [
                { title: 'Community committees', body: 'Set priorities in neighborhoods and shelters.' },
                { title: 'Volunteers', body: 'Field support under a code of conduct.' },
                { title: 'Local initiatives', body: 'Youth- and women-led efforts we aim to back.' },
                { title: 'Institutional partners', body: 'Resources and expertise in clear agreements.' },
            ],
        },
        partners: {
            eyebrow: 'Partnerships',
            title: 'Together toward a more integrated humanitarian response',
            lead: 'We aspire to build effective partnerships. The categories below are planned areas of cooperation, not existing agreements.',
            categories: [
                'Government and local institutions', 'International and Arab organizations',
                'Universities and research centers', 'Private sector and civil society',
            ],
        },
        knowledge: {
            eyebrow: 'Knowledge and media',
            title: 'Reports, studies and stories',
            note: 'Demonstration content only — not real organizational news or activities.',
            articles: [
                { tag: 'Demo · News & reports', title: 'Sample report headline' },
                { tag: 'Demo · Studies & needs', title: 'Sample needs-assessment title' },
                { tag: 'Demo · Community stories', title: 'Sample community story' },
            ],
        },
        cta: {
            title: 'Because recovery is a shared responsibility',
            lead: 'We believe the most effective humanitarian work begins with cooperation, continues with participation, and builds a future more able to withstand crises.',
            contact: 'Contact us',
            volunteer: 'Become a volunteer',
            explore: 'Explore our areas of work',
        },
        footer: {
            description: 'Participatory Action Association for Crisis and Disaster Response – Gaza Strip. Prototype of the digital identity; content is drawn from the 2027–2029 strategic plan.',
            contactTitle: 'Contact',
            contactBody: 'Contact details, address and social accounts will be added once confirmed.',
        },
    },
}

const { locale, direction, alternateUrl, localPath } = useI18n()

function routeFor(anchor) {
    const routes = {
        '#top': localPath(),
        '#about': localPath('about'),
        '#areas': localPath('strategic-areas'),
        '#programs': localPath('programs'),
        '#targets': localPath('about/direction'),
        '#knowledge': localPath('publications'),
        '#contact': localPath('contact'),
        '#partners': localPath('partnerships'),
        '#footer': localPath('contact'),
        '#community': localPath('get-involved'),
    }

    return routes[anchor] || anchor
}

const copy = computed(() => content[locale.value] || content.ar)
const otherLocale = computed(() => (locale.value === 'ar' ? 'en' : 'ar'))
const title = computed(() => (
    locale.value === 'ar'
        ? 'PCDR | جمعية العمل التشاركي في الأزمات والكوارث'
        : 'PCDR | Participatory Action in Crises and Disasters'
))
const description = computed(() => props.meta?.description
    || 'من الاستجابة الإنسانية إلى التعافي والتنمية المستدامة — PCDR، قطاع غزة.')

const logoSmall = '/assets/pcdr-homepage/logo/pcdr-logo.png'
const logoAnimated = '/assets/pcdr-homepage/logo/pcdr-logo-animated.svg'

const observers = []
const timers = []
const listenerRemovers = []

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function cleanupMotion() {
    observers.splice(0).forEach((observer) => observer.disconnect())
    timers.splice(0).forEach(clearTimeout)
    listenerRemovers.splice(0).forEach((remove) => remove())
    document.documentElement.classList.remove('js', 'ready')
    document.documentElement.style.removeProperty('scroll-behavior')
    document.documentElement.style.removeProperty('scroll-padding-top')
    document.body.classList.remove('pcdr-menu-open')
}

function setupMotion() {
    const shell = root.value
    if (! shell) {
        return
    }

    document.documentElement.classList.add('js')

    shell.querySelectorAll('[data-stagger]').forEach((group) => {
        [...group.children].forEach((child, index) => child.style.setProperty('--i', String(index)))
    })

    const reduced = prefersReducedMotion()
    if (reduced) {
        document.documentElement.classList.add('ready')
        intro.value?.remove()
        return
    }

    document.documentElement.style.scrollBehavior = 'smooth'
    document.documentElement.style.scrollPaddingTop = '76px'

    if (header.value) {
        const sentinel = document.getElementById('sentinel')
        if (sentinel && 'IntersectionObserver' in window) {
            const headerObserver = new IntersectionObserver(([entry]) => {
                header.value?.classList.toggle('solid', ! entry.isIntersecting)
            })
            headerObserver.observe(sentinel)
            observers.push(headerObserver)
        }
    }

    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) {
                    return
                }
                entry.target.classList.add('in')
                observer.unobserve(entry.target)
            })
        }, { threshold: 0.15, rootMargin: '0px 0px -6% 0px' })
        shell.querySelectorAll('.rv, .steps').forEach((element) => revealObserver.observe(element))
        observers.push(revealObserver)

        const vm = shell.querySelector('.vm')
        if (vm) {
            const vmObserver = new IntersectionObserver(([entry], observer) => {
                if (entry.isIntersecting) {
                    vm.classList.add('in')
                    observer.disconnect()
                }
            }, { threshold: 0.15 })
            vmObserver.observe(vm)
            observers.push(vmObserver)
        }

        const format = (value) => value.toLocaleString('en-US')
        const countObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) {
                    return
                }
                observer.unobserve(entry.target)
                const element = entry.target
                const end = Number(element.dataset.count || 0)
                const started = performance.now()
                const duration = 1600

                const tick = (now) => {
                    const progress = Math.min((now - started) / duration, 1)
                    element.textContent = format(Math.round(end * (1 - Math.pow(1 - progress, 3))))
                    if (progress < 1) {
                        requestAnimationFrame(tick)
                    }
                }
                requestAnimationFrame(tick)
            })
        }, { threshold: 0.6 })
        shell.querySelectorAll('[data-count]').forEach((element) => countObserver.observe(element))
        observers.push(countObserver)
    }

    const introElement = intro.value
    timers.push(setTimeout(() => {
        introElement?.classList.add('out')
        document.documentElement.classList.add('ready')
        timers.push(setTimeout(() => introElement?.remove(), 1000))
    }, 1250))

    const onKeydown = (event) => {
        if (event.key === 'Escape') {
            navOpen.value = false
            document.getElementById('burger')?.focus()
        }
    }
    window.addEventListener('keydown', onKeydown)
    listenerRemovers.push(() => window.removeEventListener('keydown', onKeydown))

    if (! window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        return
    }

    const hero = shell.querySelector('.hero')
    if (hero && heroArt.value) {
        const onPointerMove = (event) => {
            const rect = hero.getBoundingClientRect()
            heroArt.value.style.setProperty('--px', (((event.clientX - rect.left) / rect.width) - 0.5) * 2)
            heroArt.value.style.setProperty('--py', (((event.clientY - rect.top) / rect.height) - 0.5) * 2)
        }
        hero.addEventListener('pointermove', onPointerMove)
        listenerRemovers.push(() => hero.removeEventListener('pointermove', onPointerMove))
    }

    shell.querySelectorAll('.tile').forEach((tile) => {
        const onMove = (event) => {
            const rect = tile.getBoundingClientRect()
            const x = (event.clientX - rect.left) / rect.width
            const y = (event.clientY - rect.top) / rect.height
            tile.style.setProperty('--mx', `${x * 100}%`)
            tile.style.setProperty('--my', `${y * 100}%`)
            tile.style.setProperty('--rx', `${(0.5 - y) * 6}deg`)
            tile.style.setProperty('--ry', `${(x - 0.5) * 8}deg`)
        }
        const onLeave = () => {
            tile.style.removeProperty('--rx')
            tile.style.removeProperty('--ry')
        }
        tile.addEventListener('pointermove', onMove)
        tile.addEventListener('pointerleave', onLeave)
        listenerRemovers.push(() => {
            tile.removeEventListener('pointermove', onMove)
            tile.removeEventListener('pointerleave', onLeave)
        })
    })

    shell.querySelectorAll('.btn').forEach((button) => {
        const onMove = (event) => {
            const rect = button.getBoundingClientRect()
            const x = (event.clientX - rect.left - rect.width / 2) * 0.18
            const y = (event.clientY - rect.top - rect.height / 2) * 0.28
            button.style.transform = `translate(${x}px, ${y}px)`
        }
        const onLeave = () => { button.style.transform = '' }
        button.addEventListener('pointermove', onMove)
        button.addEventListener('pointerleave', onLeave)
        listenerRemovers.push(() => {
            button.removeEventListener('pointermove', onMove)
            button.removeEventListener('pointerleave', onLeave)
        })
    })
}

onMounted(setupMotion)
onBeforeUnmount(cleanupMotion)
</script>

<template>
    <div ref="root" class="pcdr-home" :lang="locale" :dir="direction">
        <Head>
            <title>{{ title }}</title>
            <meta name="description" :content="description">
            <link v-if="props.meta?.canonical" rel="canonical" :href="props.meta.canonical">
            <link rel="alternate" hreflang="ar" :href="alternateUrl('ar')">
            <link rel="alternate" hreflang="en" :href="alternateUrl('en')">
            <meta name="robots" :content="props.meta?.robots || 'index,follow'">
            <meta property="og:type" content="website">
            <meta property="og:title" :content="title">
            <meta property="og:description" :content="description">
            <meta property="og:locale" :content="locale">
            <meta property="og:site_name" content="PCDR">
        </Head>

        <div id="intro" ref="intro" aria-hidden="true">
            <img :src="logoSmall" alt="" width="132" height="132">
            <i></i>
        </div>
        <div class="progress" aria-hidden="true"></div>
        <a class="skip" href="#main">{{ copy.skip }}</a>
        <i id="sentinel" aria-hidden="true"></i>

        <header ref="header" class="site-header">
            <div class="container bar">
                <a class="brand" :href="routeFor('#top')" aria-label="PCDR">
                    <img :src="logoSmall" width="52" height="52" :alt="copy.logoAlt">
                    <b>PCDR</b>
                </a>

                <nav id="nav" :class="{ open: navOpen }" :aria-label="copy.navAria">
                    <ul class="nav-links">
                        <li v-for="link in copy.nav" :key="link.href">
                            <a :href="routeFor(link.href)" @click="navOpen = false">{{ link.label }}</a>
                        </li>
                    </ul>
                    <div class="nav-panel-extra">
                        <a class="btn btn-primary nav-panel-cta" :href="routeFor('#partners')" @click="navOpen = false">{{ copy.partner }}</a>
                        <div class="nav-lang-row">
                            <span class="nav-lang-label">{{ copy.langRowLabel }}</span>
                            <LanguageSwitcher />
                        </div>
                    </div>
                </nav>

                <div class="actions">
                    <LanguageSwitcher class="actions-lang" />
                    <a class="btn btn-primary hide-sm" :href="routeFor('#partners')">{{ copy.partner }}</a>
                    <button
                        id="burger"
                        class="burger"
                        type="button"
                        :aria-expanded="String(navOpen)"
                        aria-controls="nav"
                        :aria-label="copy.navAria"
                        @click="navOpen = !navOpen"
                    >
                        <span></span><span></span><span></span>
                    </button>
                </div>
            </div>
        </header>

        <main id="main">
            <section class="hero" id="top">
                <div class="container hero-grid">
                    <div class="hero-copy">
                        <p class="eyebrow fx">{{ copy.hero.eyebrow }}</p>
                        <h1 data-split><SplitText :text="copy.hero.title" /></h1>
                        <p class="lead fx">{{ copy.hero.lead }}</p>
                        <div class="cta-row fx">
                            <a class="btn btn-primary" :href="routeFor('#about')">{{ copy.hero.discover }}</a>
                            <a class="btn btn-ghost" :href="routeFor('#partners')">{{ copy.hero.partner }}</a>
                        </div>
                    </div>

                    <div ref="heroArt" class="hero-art" aria-hidden="true">
                        <i class="rg"></i><i class="rg"></i><i class="rg"></i>
                        <div class="orbit"><b></b><b></b><b></b></div>
                        <svg class="drawring" viewBox="0 0 100 100"><circle cx="50" cy="50" r="49" /></svg>
                        <div class="badge-wrap">
                            <div class="badge-logo">
                                <img :src="logoAnimated" width="1130" height="1130" alt="">
                                <span class="sheen"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="wave" aria-hidden="true"><i></i><i class="w2"></i></div>
            </section>

            <section class="sec about" id="about">
                <div class="container">
                    <p class="eyebrow rv">{{ copy.about.eyebrow }}</p>
                    <h2 data-split class="rv"><SplitText :text="copy.about.title" /></h2>
                    <div class="vm" data-stagger>
                        <article class="vision rv">
                            <h3>{{ copy.about.visionTitle }}</h3>
                            <p>{{ copy.about.vision }}</p>
                        </article>
                        <article class="mission rv">
                            <h3>{{ copy.about.missionTitle }}</h3>
                            <p>{{ copy.about.mission }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="sec areas" id="areas">
                <div class="container">
                    <p class="eyebrow rv">{{ copy.areas.eyebrow }}</p>
                    <h2 data-split class="rv"><SplitText :text="copy.areas.title" /></h2>
                    <ol class="pillars" data-stagger>
                        <li v-for="(pillar, index) in copy.areas.pillars" :key="pillar.title" class="rv">
                            <span class="n">{{ String(index + 1).padStart(2, '0') }}</span>
                            <h3>{{ pillar.title }}</h3>
                            <p>{{ pillar.body }}</p>
                        </li>
                    </ol>
                </div>
            </section>

            <section class="sec approach" id="approach">
                <div class="container">
                    <p class="eyebrow rv">{{ copy.approach.eyebrow }}</p>
                    <h2 data-split class="rv"><SplitText :text="copy.approach.title" /></h2>
                    <ol class="steps" data-stagger>
                        <li v-for="(step, index) in copy.approach.steps" :key="step.title">
                            <span class="dot">{{ index + 1 }}</span>
                            <h3>{{ step.title }}</h3>
                            <p>{{ step.body }}</p>
                        </li>
                    </ol>
                </div>
            </section>

            <section class="sec programs" id="programs">
                <div class="container">
                    <p class="eyebrow rv">{{ copy.programs.eyebrow }}</p>
                    <h2 data-split class="rv"><SplitText :text="copy.programs.title" /></h2>
                    <p class="note rv">{{ copy.programs.note }}</p>
                    <div class="bento" data-stagger>
                        <a
                            v-for="(tile, index) in copy.programs.tiles"
                            :key="tile"
                            class="tile rv"
                            :class="`t${index + 1}`"
                            :href="routeFor('#programs')"
                        >
                            <small>{{ String(index + 1).padStart(2, '0') }}</small>
                            <h3>{{ tile }}</h3>
                        </a>
                    </div>
                </div>
            </section>

            <section class="sec targets" id="targets">
                <div class="container">
                    <span class="badge rv">{{ copy.targets.badge }}</span>
                    <h2 data-split class="rv"><SplitText :text="copy.targets.title" /></h2>
                    <p class="note rv">{{ copy.targets.note }}</p>
                    <dl class="stats" data-stagger>
                        <div v-for="stat in copy.targets.stats" :key="stat.value" class="rv">
                            <dt class="num" :data-count="stat.value">{{ stat.display }}</dt>
                            <dd>{{ stat.label }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="sec community" id="community">
                <div class="container split">
                    <div>
                        <p class="eyebrow rv">{{ copy.community.eyebrow }}</p>
                        <h2 data-split class="rv"><SplitText :text="copy.community.title" /></h2>
                        <p class="rv">{{ copy.community.body }}</p>
                    </div>
                    <ul class="roles" data-stagger>
                        <li v-for="role in copy.community.roles" :key="role.title" class="rv">
                            <b>{{ role.title }}</b>
                            <span>{{ role.body }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="sec partners" id="partners">
                <div class="container">
                    <p class="eyebrow rv">{{ copy.partners.eyebrow }}</p>
                    <h2 data-split class="rv"><SplitText :text="copy.partners.title" /></h2>
                    <p class="rv lead">{{ copy.partners.lead }}</p>
                    <ul class="cats" data-stagger>
                        <li v-for="category in copy.partners.categories" :key="category" class="rv">
                            {{ category }}
                        </li>
                    </ul>
                </div>
            </section>

            <section class="sec knowledge" id="knowledge">
                <div class="container">
                    <p class="eyebrow rv">{{ copy.knowledge.eyebrow }}</p>
                    <h2 data-split class="rv"><SplitText :text="copy.knowledge.title" /></h2>
                    <p class="note rv">{{ copy.knowledge.note }}</p>
                    <div class="news" data-stagger>
                        <article v-for="article in copy.knowledge.articles" :key="article.title" class="rv">
                            <span class="tag">{{ article.tag }}</span>
                            <h3>{{ article.title }}</h3>
                        </article>
                    </div>
                </div>
            </section>

            <section class="sec cta" id="contact">
                <div class="container">
                    <h2 data-split class="rv"><SplitText :text="copy.cta.title" /></h2>
                    <p class="rv lead">{{ copy.cta.lead }}</p>
                    <div class="cta-row rv">
                        <a class="btn btn-primary" :href="routeFor('#footer')">{{ copy.cta.contact }}</a>
                        <a class="btn btn-ghost" :href="routeFor('#community')">{{ copy.cta.volunteer }}</a>
                        <a class="btn btn-ghost" :href="routeFor('#areas')">{{ copy.cta.explore }}</a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="site-footer" id="footer">
            <div class="container fgrid">
                <div>
                    <a class="brand" :href="routeFor('#top')">
                        <img :src="logoSmall" width="56" height="56" alt="شعار PCDR">
                        <b>PCDR</b>
                    </a>
                    <p>{{ copy.footer.description }}</p>
                </div>
                <ul>
                    <li><a :href="routeFor('#about')">{{ copy.nav[1].label }}</a></li>
                    <li><a :href="routeFor('#areas')">{{ copy.nav[2].label }}</a></li>
                    <li><a :href="routeFor('#programs')">{{ copy.nav[3].label }}</a></li>
                    <li><a :href="routeFor('#targets')">{{ copy.nav[4].label }}</a></li>
                </ul>
                <div>
                    <h3>{{ copy.footer.contactTitle }}</h3>
                    <p>{{ copy.footer.contactBody }}</p>
                </div>
            </div>
            <p class="copy container">© 2026 PCDR</p>
        </footer>
    </div>
</template>
