<script setup>
const emit = defineEmits(['go-back'])
const paypalUrl = 'https://www.paypal.me/jorgesinmas'
</script>

<template>
  <div class="contact-backdrop">
    <div class="contact-panel">
      <header class="contact-header">
        <div class="contact-ornament">
          <span class="contact-ornament__line"></span>
          <span class="contact-ornament__glyph">&#10070;</span>
          <span class="contact-ornament__line"></span>
        </div>
        <h2 class="contact-title">Contacto</h2>
      </header>

      <div class="contact-content">
        <section class="contact-section">
          <h3 class="contact-section__title">Escríbanos</h3>
          <p class="contact-text">Si tiene alguna duda o sugerencia puede escribirnos a:</p>
          <a href="mailto:suekanoo@gmail.com" class="contact-email">suekanoo@gmail.com</a>
        </section>
        <section class="contact-section">
          <h3 class="contact-section__title">Tiempo de respuesta</h3>
          <p class="contact-text">Contestaremos a tu correo en la mayor brevedad posible dependiendo de la disponibilidad del creador</p>
        </section>
        <div class="contact-divider"></div>
        <p class="contact-support">Si quieres apoyar el proyecto, puedes hacerlo <a :href="paypalUrl" target="_blank" rel="noopener" class="contact-support__link">aquí</a>.</p>
      </div>
      <footer class="contact-footer">
        <button class="contact-back" @click="emit('go-back')">Volver</button>
      </footer>
    </div>
  </div>
</template>

<style scoped src="../styles/Contact.css"></style>