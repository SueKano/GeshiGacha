<template>
  <p v-if="message" class="error-message">{{ message }}</p>
</template>

<script setup>
defineProps({
  message: { type: String, default: '' }
})
</script>

<style scoped src="../styles/ErrorMessage.css"></style>