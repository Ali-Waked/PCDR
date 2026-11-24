<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Editor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import TextAlign from '@tiptap/extension-text-align'
import Placeholder from '@tiptap/extension-placeholder'
import Highlight from '@tiptap/extension-highlight'
import Image from '@tiptap/extension-image'
import Superscript from '@tiptap/extension-superscript'
import Subscript from '@tiptap/extension-subscript'
import UiIcon from '../Dashboard/UiIcon.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    modelValue: { type: String, default: '' },
    locale: { type: String, default: 'ar' },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    readOnly: { type: Boolean, default: false },
    error: { type: String, default: '' },
    id: { type: String, default: '' },
    media: { type: Array, default: () => [] },
    features: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()
const editor = ref(null)
const activeDialog = ref('')
const linkUrl = ref('')
const linkError = ref('')
const imageAlt = ref('')

const enabled = computed(() => ! props.disabled && ! props.readOnly)
const direction = computed(() => (props.locale === 'ar' ? 'rtl' : 'ltr'))
const showImages = computed(() => props.features.image !== false && props.media.length > 0)
const showExtras = computed(() => props.features.highlight !== false || props.features.superscript !== false || props.features.subscript !== false)

const defaults = {
    highlight: true,
    superscript: true,
    subscript: true,
    image: true,
    divider: true,
}

function feature(name) {
    return defaults[name] !== false && props.features[name] !== false
}

function label(key, fallback) {
    return t(`ui.editor.${key}`, fallback)
}

const editorInstance = new Editor({
    content: props.modelValue || '',
    editable: enabled.value,
    editorProps: {
        attributes: {
            class: 'pc-rte__content',
            dir: direction.value,
            lang: props.locale,
            'aria-label': label('toolbar', 'Rich text editor'),
        },
    },
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3, 4] },
            link: {
                openOnClick: false,
                autolink: true,
                HTMLAttributes: { rel: 'noopener noreferrer nofollow' },
            },
        }),
        TextAlign.configure({
            types: ['heading', 'paragraph'],
            alignments: ['right', 'center', 'left', 'justify'],
            defaultAlignment: props.locale === 'ar' ? 'right' : 'left',
        }),
        Placeholder.configure({ placeholder: props.placeholder || label('placeholder', 'Start writing…') }),
        Highlight,
        Superscript,
        Subscript,
        Image.configure({ allowBase64: false, HTMLAttributes: { loading: 'lazy' } }),
    ],
    onUpdate: ({ editor: instance }) => {
        emit('update:modelValue', instance.getHTML())
    },
})

editor.value = editorInstance

function syncEditorOptions() {
    editorInstance.setOptions({
        editable: enabled.value,
        editorProps: {
            attributes: {
                class: 'pc-rte__content',
                dir: direction.value,
                lang: props.locale,
                'aria-label': label('toolbar', 'Rich text editor'),
            },
        },
    })
}

watch(() => [props.locale, props.disabled, props.readOnly], syncEditorOptions)

watch(() => props.modelValue, (value) => {
    const next = value || ''
    if (next !== editorInstance.getHTML()) {
        editorInstance.commands.setContent(next, { emitUpdate: false })
    }
})

watch(() => props.placeholder, (value) => {
    editorInstance.setOptions({
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: { openOnClick: false, autolink: true, HTMLAttributes: { rel: 'noopener noreferrer nofollow' } },
            }),
            TextAlign.configure({
                types: ['heading', 'paragraph'],
                alignments: ['right', 'center', 'left', 'justify'],
                defaultAlignment: props.locale === 'ar' ? 'right' : 'left',
            }),
            Placeholder.configure({ placeholder: value || label('placeholder', 'Start writing…') }),
            Highlight,
            Superscript,
            Subscript,
            Image.configure({ allowBase64: false, HTMLAttributes: { loading: 'lazy' } }),
        ],
    })
})

onBeforeUnmount(() => editorInstance.destroy())

function run(command) {
    if (! enabled.value) {
        return
    }
    command(editorInstance.chain().focus()).run()
}

function toggleDialog(name) {
    activeDialog.value = activeDialog.value === name ? '' : name
    linkError.value = ''
}

function submitLink() {
    if (! enabled.value) {
        return
    }
    const href = String(linkUrl.value || '').trim()
    if (! /^(https?:\/\/|mailto:|\/|#)/i.test(href)) {
        linkError.value = label('invalid_url', 'Enter a valid link.')
        return
    }

    const { from, to } = editorInstance.state.selection
    if (from === to) {
        editorInstance.chain().focus().insertContent(href).run()
        editorInstance.chain().focus().setTextSelection({ from, to: from + href.length }).setLink({ href }).run()
    } else {
        editorInstance.chain().focus().extendMarkRange('link').setLink({ href }).run()
    }

    activeDialog.value = ''
    linkUrl.value = ''
    linkError.value = ''
}

function insertImage(item) {
    if (! enabled.value) {
        return
    }
    editorInstance.chain().focus().setImage({
        src: item.url,
        alt: imageAlt.value || item.alt || item.name || '',
    }).run()
    imageAlt.value = ''
    activeDialog.value = ''
}

function clearFormatting() {
    if (! enabled.value) {
        return
    }
    editorInstance.chain().focus().unsetAllMarks().clearNodes().run()
}

function isActive(name, attributes) {
    return editorInstance?.isActive(name, attributes) || false
}
</script>

<template>
    <div class="pc-rte" :class="{ 'is-disabled': !enabled, 'has-error': !!error }">
        <div v-if="enabled" class="pc-rte__toolbar" role="toolbar" :aria-label="label('toolbar', 'Formatting tools')">
            <div class="pc-rte__group">
                <button type="button" class="pc-rte__button" :title="label('undo', 'Undo')" :aria-label="label('undo', 'Undo')" :disabled="!editorInstance.can().undo()" @click="run((chain) => chain.undo())">
                    ↶
                </button>
                <button type="button" class="pc-rte__button" :title="label('redo', 'Redo')" :aria-label="label('redo', 'Redo')" :disabled="!editorInstance.can().redo()" @click="run((chain) => chain.redo())">
                    ↷
                </button>
            </div>

            <div class="pc-rte__group">
                <button type="button" class="pc-rte__button pc-rte__button--text" :class="{ 'is-active': isActive('paragraph') }" :title="label('paragraph', 'Paragraph')" :aria-label="label('paragraph', 'Paragraph')" @click="run((chain) => chain.setParagraph())">¶</button>
                <button v-for="level in [2, 3, 4]" :key="level" type="button" class="pc-rte__button pc-rte__button--text" :class="{ 'is-active': isActive('heading', { level }) }" :title="label(`heading${level}`, `Heading ${level}`)" :aria-label="label(`heading${level}`, `Heading ${level}`)" @click="run((chain) => chain.toggleHeading({ level }))">H{{ level }}</button>
            </div>

            <div class="pc-rte__group">
                <button type="button" class="pc-rte__button pc-rte__button--text is-bold" :class="{ 'is-active': isActive('bold') }" :title="label('bold', 'Bold')" :aria-label="label('bold', 'Bold')" @click="run((chain) => chain.toggleBold())">B</button>
                <button type="button" class="pc-rte__button pc-rte__button--text is-italic" :class="{ 'is-active': isActive('italic') }" :title="label('italic', 'Italic')" :aria-label="label('italic', 'Italic')" @click="run((chain) => chain.toggleItalic())">I</button>
                <button type="button" class="pc-rte__button pc-rte__button--text is-underline" :class="{ 'is-active': isActive('underline') }" :title="label('underline', 'Underline')" :aria-label="label('underline', 'Underline')" @click="run((chain) => chain.toggleUnderline())">U</button>
                <button type="button" class="pc-rte__button pc-rte__button--text is-strike" :class="{ 'is-active': isActive('strike') }" :title="label('strike', 'Strikethrough')" :aria-label="label('strike', 'Strikethrough')" @click="run((chain) => chain.toggleStrike())">S</button>
            </div>

            <div class="pc-rte__group">
                <button type="button" class="pc-rte__button" :class="{ 'is-active': isActive('bulletList') }" :title="label('bullet_list', 'Bullet list')" :aria-label="label('bullet_list', 'Bullet list')" @click="run((chain) => chain.toggleBulletList())">
                    <UiIcon name="layers" :size="15" />
                </button>
                <button type="button" class="pc-rte__button pc-rte__button--text" :class="{ 'is-active': isActive('orderedList') }" :title="label('ordered_list', 'Numbered list')" :aria-label="label('ordered_list', 'Numbered list')" @click="run((chain) => chain.toggleOrderedList())">1.</button>
                <button type="button" class="pc-rte__button" :class="{ 'is-active': isActive('blockquote') }" :title="label('blockquote', 'Quote')" :aria-label="label('blockquote', 'Quote')" @click="run((chain) => chain.toggleBlockquote())">
                    <UiIcon name="file" :size="15" />
                </button>
                <button v-if="feature('divider')" type="button" class="pc-rte__button pc-rte__button--text" :title="label('divider', 'Divider')" :aria-label="label('divider', 'Divider')" @click="run((chain) => chain.setHorizontalRule())">—</button>
            </div>

            <div class="pc-rte__group">
                <button v-for="align in ['right', 'center', 'left', 'justify']" :key="align" type="button" class="pc-rte__button pc-rte__button--text" :class="{ 'is-active': isActive({ paragraph: true }, { textAlign: align }) }" :title="label(`align_${align}`, align)" :aria-label="label(`align_${align}`, align)" @click="run((chain) => chain.setTextAlign(align))">
                    {{ { right: '↤', center: '↔', left: '↦', justify: '☰' }[align] }}
                </button>
            </div>

            <div v-if="showExtras" class="pc-rte__group">
                <button v-if="feature('highlight')" type="button" class="pc-rte__button pc-rte__button--text" :class="{ 'is-active': isActive('highlight') }" :title="label('highlight', 'Highlight')" :aria-label="label('highlight', 'Highlight')" @click="run((chain) => chain.toggleHighlight())">✎</button>
                <button v-if="feature('superscript')" type="button" class="pc-rte__button pc-rte__button--text" :class="{ 'is-active': isActive('superscript') }" :title="label('superscript', 'Superscript')" :aria-label="label('superscript', 'Superscript')" @click="run((chain) => chain.toggleSuperscript())">x²</button>
                <button v-if="feature('subscript')" type="button" class="pc-rte__button pc-rte__button--text" :class="{ 'is-active': isActive('subscript') }" :title="label('subscript', 'Subscript')" :aria-label="label('subscript', 'Subscript')" @click="run((chain) => chain.toggleSubscript())">x₂</button>
            </div>

            <div class="pc-rte__group">
                <button type="button" class="pc-rte__button" :class="{ 'is-active': isActive('link'), 'is-open': activeDialog === 'link' }" :title="label('link', 'Link')" :aria-label="label('link', 'Link')" @click="toggleDialog('link')">
                    <UiIcon name="link" :size="15" />
                </button>
                <button type="button" class="pc-rte__button" :title="label('unlink', 'Remove link')" :aria-label="label('unlink', 'Remove link')" :disabled="!isActive('link')" @click="run((chain) => chain.unsetLink())">
                    <UiIcon name="close" :size="15" />
                </button>
                <button v-if="showImages" type="button" class="pc-rte__button" :class="{ 'is-open': activeDialog === 'image' }" :title="label('image', 'Insert image')" :aria-label="label('image', 'Insert image')" @click="toggleDialog('image')">
                    <UiIcon name="image" :size="15" />
                </button>
                <button type="button" class="pc-rte__button" :title="label('clear', 'Clear formatting')" :aria-label="label('clear', 'Clear formatting')" @click="clearFormatting">
                    <UiIcon name="refresh" :size="15" />
                </button>
            </div>

            <div v-if="activeDialog === 'link'" class="pc-rte__dialog" role="group" :aria-label="label('link_dialog_title', 'Insert link')">
                <label class="pc-rte__dialog-field">
                    <span class="sr-only">{{ label('link_url', 'Link address') }}</span>
                    <input v-model="linkUrl" type="url" inputmode="url" :placeholder="label('link_placeholder', 'https://example.org')" @keydown.enter.prevent="submitLink">
                </label>
                <button type="button" class="pc-btn pc-btn--secondary pc-btn--sm" @click="submitLink">{{ label('insert', 'Insert') }}</button>
                <button type="button" class="pc-btn pc-btn--ghost pc-btn--sm" @click="toggleDialog('link')">{{ label('cancel', 'Cancel') }}</button>
                <span v-if="linkError" class="pc-error" role="alert">{{ linkError }}</span>
            </div>

            <div v-if="activeDialog === 'image'" class="pc-rte__dialog pc-rte__dialog--image" role="group" :aria-label="label('image_dialog_title', 'Insert image')">
                <label class="pc-rte__dialog-field">
                    <span class="sr-only">{{ label('image_alt', 'Alternative text') }}</span>
                    <input v-model="imageAlt" type="text" :placeholder="label('image_alt_hint', 'Describe the image')">
                </label>
                <div class="pc-rte__media-list">
                    <button v-for="item in media" :key="item.id" type="button" class="pc-rte__media-option" @click="insertImage(item)">
                        <img :src="item.url" :alt="item.alt || item.name || ''" loading="lazy">
                        <span>{{ item.name }}</span>
                    </button>
                </div>
                <button type="button" class="pc-btn pc-btn--ghost pc-btn--sm" @click="toggleDialog('image')">{{ label('cancel', 'Cancel') }}</button>
            </div>
        </div>

        <div :id="id || undefined" class="pc-rte__surface" :class="{ 'is-readonly': !enabled }">
            <EditorContent :editor="editorInstance" />
        </div>
        <p v-if="error" class="pc-error" role="alert">{{ error }}</p>
    </div>
</template>
