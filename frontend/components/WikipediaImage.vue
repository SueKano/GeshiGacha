<script setup>
import { computed } from 'vue'
import { getWikipediaUrl } from '../scripts/wikipedia.js'

const props = defineProps({
  urlImage: { type: String, default: null },
  name: { type: String, required: true },
})

const href = computed(() => getWikipediaUrl(props.name))
</script>

<template>
  <a :href="href" target="_blank" rel="noopener" class="wiki-portrait" @click.stop>
    <img v-if="urlImage" :src="urlImage" :alt="name" class="wiki-portrait__img" />
    <span v-else class="wiki-portrait__initial">{{ name.charAt(0) }}</span>
  </a>
</template>

<style scoped src="../styles/WikipediaImage.css"></style>