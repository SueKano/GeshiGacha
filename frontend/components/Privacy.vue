<script setup>
const emit = defineEmits(['go-back', 'go-contact'])
</script>

<template>
  <div class="privacy-backdrop">
    <div class="privacy-panel">
      <header class="privacy-header">
        <div class="privacy-ornament">
          <span class="privacy-ornament__line"></span>
          <span class="privacy-ornament__glyph">&#10070;</span>
          <span class="privacy-ornament__line"></span>
        </div>
        <h2 class="privacy-title">Privacidad</h2>
        <p class="privacy-subtitle">Cómo tratamos tus datos</p>
      </header>

      <div class="privacy-content">
        <section class="privacy-section">
          <h3 class="privacy-section__title">Qué datos guardamos</h3>
          <ul class="privacy-list">
            <li><strong>Correo electrónico</strong> — para que puedas entrar en tu cuenta.</li>
            <li><strong>Contraseña</strong> — se guarda de forma segura. Nadie, ni siquiera el creador, puede ver tu contraseña.</li>
            <li><strong>Datos de juego</strong> — tus cartas, batallas e invocaciones.</li>
          </ul>
        </section>

        <div class="privacy-divider"></div>
        <section class="privacy-section">
          <h3 class="privacy-section__title">Para qué los usamos</h3>
          <ul class="privacy-list">
            <li>Para que puedas iniciar sesión.</li>
            <li>Para guardar tu progreso (colección, batallas).</li>
            <li>Para controlar las tiradas diarias.</li>
          </ul>
        </section>

        <div class="privacy-divider"></div>
        <section class="privacy-section">
          <h3 class="privacy-section__title">¿Compartimos tus datos?</h3>
          <p class="privacy-text">No. Tus datos son solo tuyos. No los vendemos, cedemos ni compartimos con nadie.</p>
        </section>

        <div class="privacy-divider"></div>
        <section class="privacy-section">
          <h3 class="privacy-section__title">Contacto</h3>
          <p class="privacy-text">Si tienes alguna duda puedes visitar la página de <button class="privacy-inline-link" @click="emit('go-contact')">contacto</button>.</p>
        </section>
      </div>

      <footer class="privacy-footer">
        <button class="privacy-back" @click="emit('go-back')">Volver</button>
      </footer>
    </div>
  </div>
</template>

<style scoped src="../styles/Privacy.css"></style>