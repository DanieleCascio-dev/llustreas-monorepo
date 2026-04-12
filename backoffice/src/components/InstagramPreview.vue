<script setup>
import { InstagramCarousel } from '@illustreas/shared-ui'

const props = defineProps({
  posts: { type: Array, required: true },
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
    marquee-speed="80s"
    @reorder="onReorder"
  />
</template>
