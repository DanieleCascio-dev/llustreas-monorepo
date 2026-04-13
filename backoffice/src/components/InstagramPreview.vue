<script setup>
import { InstagramCarousel } from '@illustreas/shared-ui'

const props = defineProps({
  posts: { type: Array, required: true },
  forceMode: { type: String, default: null },
  marqueeSpeed: { type: String, default: '80s' },
})

const emit = defineEmits(['reorder'])

function onReorder(fromId, toId) {
  emit('reorder', fromId, toId)
}
</script>

<template>
  <InstagramCarousel
    :posts="props.posts"
    draggable
    section-radius="12px"
    :marquee-speed="props.marqueeSpeed"
    :force-mode="props.forceMode"
    :mobile-scroll="props.forceMode === 'mobile'"
    @reorder="onReorder"
  />
</template>
