<script setup>
import GachaPull from './components/GachaPull.vue'
import Battle from "./components/Battle.vue";
import Login from "./components/Login.vue";
import Collection from "./components/Collection.vue";
import Privacy from "./components/Privacy.vue";
import Contact from "./components/Contact.vue";
import { useAuth } from './scripts/useAuth.js'
import {ref} from "vue";

const activeSection = ref('invocar')
const { isAuthenticated, logout } = useAuth()

function onLoginSuccess() {
  activeSection.value = 'invocar'
}
</script>

<template>
  <nav class="nav">
    <a href="/" class="nav-brand" @click.prevent="activeSection = 'invocar'">
      <span class="nav-brand-text">GG</span>
    </a>

    <div class="nav-links">
      <button class="nav-link" :class="{ 'nav-link--active': activeSection === 'invocar' }" @click.prevent="activeSection = 'invocar'">Invocar</button>
      <button class="nav-link" :class="{ 'nav-link--active': activeSection === 'coleccion' }" @click.prevent="activeSection = 'coleccion'">Colección</button>
      <button class="nav-link" :class="{ 'nav-link--active': activeSection === 'batalla' }" @click.prevent="activeSection = 'batalla'">Batalla</button>
    </div>
    <button class="nav-login" v-if="!isAuthenticated" @click.prevent="activeSection = 'login'">Iniciar sesión</button>
    <button class="nav-login" v-if="isAuthenticated" @click="logout" @click.prevent="activeSection = 'invocar'">Cerrar sesión</button>
  </nav>

  <div class="app-scene">
    <main class="app-main">
      <GachaPull v-if="activeSection === 'invocar'" />
      <Battle v-else-if="activeSection === 'batalla'"/>
      <Login v-else-if="activeSection === 'login'" mode="login" @go-register="activeSection = 'register'" @login-success="onLoginSuccess" @go-privacy="activeSection = 'privacidad'" />
      <Login v-else-if="activeSection === 'register'" mode="register" @go-login="activeSection = 'login'" @register-success="activeSection = 'invocar'" @go-privacy="activeSection = 'privacidad'" />
      <Collection v-else-if="activeSection === 'coleccion'" />
      <Privacy v-else-if="activeSection === 'privacidad'" @go-back="activeSection = 'invocar'" @go-contact="activeSection = 'contacto'" />
      <Contact v-else-if="activeSection === 'contacto'" @go-back="activeSection = 'invocar'" />
    </main>
  </div>

  <footer class="app-footer" v-if="activeSection !== 'privacidad' && activeSection !== 'contacto'">
    <button class="app-footer-link" @click="activeSection = 'privacidad'">Política de Privacidad</button>
    <span class="app-footer-separator">·</span>
    <button class="app-footer-link" @click="activeSection = 'contacto'">Contacto</button>
  </footer>
</template>

<style scoped src="./App.css"></style>