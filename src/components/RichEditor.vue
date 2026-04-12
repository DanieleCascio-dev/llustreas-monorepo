<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import TextAlign from '@tiptap/extension-text-align'
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps({ modelValue: { type: String, default: '' } })
const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({ underline: false }),
    Underline,
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
  ],
  onUpdate: () => {
    emit('update:modelValue', editor.value.getHTML())
  },
})

watch(() => props.modelValue, (val) => {
  if (editor.value && val !== editor.value.getHTML()) {
    editor.value.commands.setContent(val, false)
  }
})

onBeforeUnmount(() => {
  editor.value?.destroy()
})
</script>

<template>
  <div class="editor-wrap">
    <div v-if="editor" class="editor-toolbar">
      <button type="button" :class="{ active: editor.isActive('bold') }" @click="editor.chain().focus().toggleBold().run()"><b>B</b></button>
      <button type="button" :class="{ active: editor.isActive('italic') }" @click="editor.chain().focus().toggleItalic().run()"><i>I</i></button>
      <button type="button" :class="{ active: editor.isActive('underline') }" @click="editor.chain().focus().toggleUnderline().run()"><u>U</u></button>
      <span class="toolbar-sep"></span>
      <button type="button" :class="{ active: editor.isActive('bulletList') }" @click="editor.chain().focus().toggleBulletList().run()">•</button>
      <button type="button" @click="editor.chain().focus().setHardBreak().run()">↵</button>
    </div>
    <EditorContent :editor="editor" class="editor-content" />
  </div>
</template>

<style scoped>
.editor-wrap{border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.editor-toolbar{display:flex;gap:2px;padding:6px 8px;background:#fafafa;border-bottom:1px solid var(--border)}
.editor-toolbar button{width:30px;height:28px;border:none;background:transparent;border-radius:4px;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.editor-toolbar button:hover{background:var(--border)}
.editor-toolbar button.active{background:var(--primary-light);color:var(--primary)}
.toolbar-sep{width:1px;background:var(--border);margin:0 4px}
.editor-content{padding:12px;min-height:120px}
.editor-content :deep(.tiptap){outline:none;min-height:100px}
.editor-content :deep(.tiptap p){margin-bottom:8px}
</style>
