<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../../composables/useI18n'

const { page, t, site, localPath } = useI18n()

const logo = '/assets/pcdr-homepage/logo/pcdr-logo.png'
const primary = computed(() => page.props.navigation?.primary || page.props.navigation?.header || [])
const footer = computed(() => page.props.navigation?.footer || [])
const contact = computed(() => site.value.contact || {})
const social = computed(() => site.value.social || [])
const hasContact = computed(() => Object.values(contact.value).some(Boolean))
const year = new Date().getFullYear()
</script>

<template>
    <footer class="ps-footer">
        <div class="ps-container">
            <div class="ps-footer__grid">
                <div>
                    <Link :href="localPath()" class="ps-brand" :aria-label="site.name || 'PCDR'">
                        <img :src="logo" width="52" height="52" :alt="site.shortName || 'PCDR'">
                        <span class="ps-brand__text">
                            <strong>{{ site.shortName || 'PCDR' }}</strong>
                            <span>{{ site.region }}</span>
                        </span>
                    </Link>
                    <p>{{ site.slogan }}</p>
                    <p style="margin-block-start: 0.8rem; font-size: 0.82rem">{{ t('planning_targets_notice') }}</p>
                </div>

                <div>
                    <h3>{{ t('cms_menu_primary') }}</h3>
                    <ul>
                        <li v-for="item in primary" :key="`footer-${item.url}`">
                            <Link v-if="! item.external" :href="item.url">{{ item.label }}</Link>
                            <a v-else :href="item.url" target="_blank" rel="noopener noreferrer">{{ item.label }}</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3>{{ t('contact_details') }}</h3>
                    <p v-if="! hasContact" style="font-size: 0.85rem">{{ t('ui.no_verified_contact') }}</p>
                    <ul v-else>
                        <li v-if="contact.email"><a :href="`mailto:${contact.email}`" dir="ltr">{{ contact.email }}</a></li>
                        <li v-if="contact.phone" dir="ltr">{{ contact.phone }}</li>
                        <li v-if="contact.address">{{ contact.address }}</li>
                        <li v-if="contact.hours">{{ contact.hours }}</li>
                    </ul>

                    <template v-if="social.length">
                        <h3 style="margin-block-start: 1.4rem">{{ t('social_links') }}</h3>
                        <ul>
                            <li v-for="link in social" :key="link.url">
                                <a :href="link.url" target="_blank" rel="noopener noreferrer">{{ link.label }}</a>
                            </li>
                        </ul>
                    </template>

                    <template v-if="footer.length">
                        <h3 style="margin-block-start: 1.4rem">{{ t('cms_menu_footer') }}</h3>
                        <ul>
                            <li v-for="item in footer" :key="`footer-menu-${item.url}`">
                                <Link v-if="! item.external" :href="item.url">{{ item.label }}</Link>
                                <a v-else :href="item.url" target="_blank" rel="noopener noreferrer">{{ item.label }}</a>
                            </li>
                        </ul>
                    </template>
                </div>
            </div>

            <div class="ps-footer__bottom">
                <span>© {{ year }} {{ site.shortName || 'PCDR' }}</span>
                <span>{{ site.region }}</span>
                <span>{{ site.slogan }}</span>
            </div>
        </div>
    </footer>
</template>
