<script setup>
defineProps({
  urlImage: { type: String, default: null },
  name: { type: String, required: true },
  href: { type: String, required: true },
})
</script>

<template>
  <a :href="href" target="_blank" rel="noopener" class="wiki-portrait" @click.stop>
    <img v-if="urlImage" :src="urlImage" :alt="name" class="wiki-portrait__img" />
    <span v-else class="wiki-portrait__initial">{{ name.charAt(0) }}</span>
    <span class="wiki-portrait__overlay" aria-hidden="true">Ir a Wikipedia</span>
  </a>
</template>

<style scoped src="../styles/WikipediaImage.css"></style>