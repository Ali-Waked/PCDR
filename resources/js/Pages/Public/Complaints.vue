<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../Components/Public/PublicPageHero.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import UiIcon from '../../Components/Dashboard/UiIcon.vue'
import { useI18n } from '../../composables/useI18n'
import { complaintsMailto, copyComplaintsEmail } from '../../Support/complaintsEmail'
import '../../../css/complaints-box.css'

const props = defineProps({
    complaintsEmail: { type: String, default: '' },
    meta: { type: Object, default: null },
})

const { t, site, localPath } = useI18n()

const email = computed(() => String(props.complaintsEmail || '').trim())
const mailto = computed(() => complaintsMailto(email.value))
const generalEmail = computed(() => String(site.value.contact?.email || '').trim())
const showAlternative = computed(
    () => generalEmail.value !== '' && generalEmail.value.toLowerCase() !== email.value.toLowerCase(),
)

const guidance = computed(() => [
    t('ui.complaints.subject'),
    t('ui.complaints.description'),
    t('ui.complaints.date_location'),
    t('ui.complaints.contact_info'),
    t('ui.complaints.attachments'),
])

const feedback = ref({ message: '', tone: 'idle' })
const copyBusy = ref(false)
let feedbackTimer = null

async function copyEmail() {
    if (! email.value || copyBusy.value) {
        return
    }

    copyBusy.value = true
    const result = await copyComplaintsEmail(email.value)
    copyBusy.value = false

    feedback.value = result.ok
        ? { message: t('ui.complaints.copied'), tone: 'success' }
        : { message: t('ui.complaints.copy_failed'), tone: 'error' }

    if (feedbackTimer !== null) {
        clearTimeout(feedbackTimer)
    }
    feedbackTimer = setTimeout(() => {
        feedback.value = { message: '', tone: 'idle' }
    }, 6000)
}

onBeforeUnmount(() => {
    if (feedbackTimer !== null) {
        clearTimeout(feedbackTimer)
    }
})
</script>

<template>
    <PublicLayout :meta="meta" page-class="complaints-page">
        <PublicPageHero
            :title="t('nav.complaints')"
            :lead="t('ui.complaints.intro')"
            :breadcrumbs="[{ label: t('nav.complaints') }]"
            accent="sand"
            motif="envelope"
        />

        <section class="ps-section ps-section--tight complaints-channel" aria-labelledby="complaints-email-heading">
            <div class="ps-container">
                <div class="complaints-channel__panel">
                    <div class="complaints-channel__intro">
                        <span class="ps-eyebrow">{{ t('ui.complaints.channel_label') }}</span>
                        <h2 id="complaints-email-heading">{{ t('ui.complaints.email_label') }}</h2>
                        <p class="ps-note">{{ t('ui.complaints.panel_note') }}</p>
                    </div>

                    <div v-if="email" class="complaints-channel__action">
                        <a :href="mailto" class="complaints-channel__email" dir="ltr">
                            <UiIcon name="mail" :size="22" />
                            <span>{{ email }}</span>
                        </a>
                        <button
                            type="button"
                            class="ps-btn ps-btn-ghost complaints-copy"
                            :disabled="copyBusy"
                            :aria-busy="String(copyBusy)"
                            @click="copyEmail"
                        >
                            <UiIcon :name="copyBusy ? 'clock' : 'copy'" :size="17" />
                            {{ t('ui.complaints.copy') }}
                        </button>
                    </div>
                    <p v-else class="ps-notice">{{ t('ui.complaints.not_configured') }}</p>

                    <p
                        class="complaints-feedback"
                        :class="`is-${feedback.tone}`"
                        role="status"
                        aria-live="polite"
                        aria-atomic="true"
                    >{{ feedback.message }}</p>
                </div>
            </div>
        </section>

        <section class="ps-section complaints-how">
            <div class="ps-container complaints-how__grid">
                <SectionHeading
                    :eyebrow="t('nav.complaints')"
                    :title="t('ui.complaints.how_title')"
                />
                <div class="complaints-how__body">
                    <span class="ps-card__code" aria-hidden="true">01</span>
                    <p>{{ t('ui.complaints.how_body') }}</p>
                </div>
            </div>
        </section>

        <section class="ps-section ps-section--tight ps-surface-sand complaints-guidance">
            <div class="ps-container">
                <SectionHeading
                    :title="t('ui.complaints.guidance_title')"
                    :body="t('ui.complaints.guidance_note')"
                />
                <ul class="complaints-checklist">
                    <li v-for="(item, index) in guidance" :key="item">
                        <span class="complaints-checklist__mark" aria-hidden="true">
                            <UiIcon name="check" :size="14" />
                        </span>
                        <span class="complaints-checklist__num">{{ String(index + 1).padStart(2, '0') }}</span>
                        <span>{{ item }}</span>
                    </li>
                </ul>
            </div>
        </section>

        <section class="ps-section complaints-privacy" aria-labelledby="complaints-privacy-heading">
            <div class="ps-container complaints-privacy__inner">
                <span class="complaints-privacy__icon" aria-hidden="true">
                    <UiIcon name="shield" :size="24" />
                </span>
                <div>
                    <h2 id="complaints-privacy-heading">{{ t('ui.complaints.privacy_title') }}</h2>
                    <p class="ps-lede">{{ t('ui.complaints.privacy_body') }}</p>
                </div>
            </div>
        </section>

        <section v-if="showAlternative" class="ps-section--tight complaints-alternative">
            <div class="ps-container">
                <div class="complaints-alternative__inner">
                    <div class="complaints-alternative__heading">
                        <h2>{{ t('ui.complaints.alternative_title') }}</h2>
                        <p>{{ t('ui.complaints.alternative_note') }}</p>
                    </div>
                    <a :href="`mailto:${generalEmail}`" class="complaints-alternative__email" dir="ltr">{{ generalEmail }}</a>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
