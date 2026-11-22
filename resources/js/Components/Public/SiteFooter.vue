<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../../composables/useI18n'

const { page, t, site, localPath } = useI18n()

const primary = computed(() => page.props.navigation?.header || [])
const footer = computed(() => page.props.navigation?.footer || [])
const contact = computed(() => site.value.contact || {})
const social = computed(() => site.value.social || [])
const hasContact = computed(() => Object.values(contact.value).some(Boolean))
</script>

<template>
    <footer class="site-footer">
        <div class="container-public">
            <div class="footer-grid">
                <div>
                    <Link :href="localPath()" class="wordmark" style="color: #FFFFFF">
                        <span class="wordmark__mark" style="background: var(--brand-teal); color: var(--brand-navy-deep)" aria-hidden="true">P</span>
                        <span class="wordmark__text">
                            <strong>{{ site.shortName || 'PCDR' }}</strong>
                            <span style="color: #DCE4EF">{{ site.region }}</span>
                        </span>
                    </Link>
                    <p style="margin-block-start: 1rem; color: #DCE4EF; max-inline-size: 42ch">{{ site.slogan }}</p>
                </div>

                <div>
                    <h3 style="font-size: 0.9rem; margin-block: 0 0.9rem">{{ t('cms_menu_primary') }}</h3>
                    <ul>
                        <li v-for="item in primary" :key="`footer-${item.url}`">
                            <a :href="item.url" :target="item.external ? '_blank' : undefined" :rel="item.external ? 'noopener noreferrer' : undefined">{{ item.label }}</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 style="font-size: 0.9rem; margin-block: 0 0.9rem">{{ t('contact_details') }}</h3>
                    <p v-if="! hasContact" style="color: #DCE4EF; font-size: 0.85rem">{{ t('ui.no_verified_contact') }}</p>
                    <ul v-else>
                        <li v-if="contact.email"><a :href="`mailto:${contact.email}`" dir="ltr">{{ contact.email }}</a></li>
                        <li v-if="contact.phone" dir="ltr">{{ contact.phone }}</li>
                        <li v-if="contact.address">{{ contact.address }}</li>
                        <li v-if="contact.hours">{{ contact.hours }}</li>
                    </ul>
                    <ul v-if="social.length" style="margin-block-start: 0.9rem">
                        <li v-for="link in social" :key="link.url">
                            <a :href="link.url" target="_blank" rel="noopener noreferrer">{{ link.label }}</a>
                        </li>
                    </ul>
                    <ul v-if="footer.length" style="margin-block-start: 0.9rem">
                        <li v-for="item in footer" :key="item.url">
                            <a :href="item.url" :target="item.external ? '_blank' : undefined" :rel="item.external ? 'noopener noreferrer' : undefined">{{ item.label }}</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© {{ new Date().getFullYear() }} {{ site.shortName || 'PCDR' }}</span>
                <span>{{ site.slogan }}</span>
            </div>
        </div>
    </footer>
</template>
