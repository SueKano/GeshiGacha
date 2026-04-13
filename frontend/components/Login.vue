<script setup>
import { ref, computed } from 'vue'
import { useAuth } from '../scripts/useAuth.js'
import { API_URL } from '../scripts/api.js'
import ErrorMessage from './ErrorMessage.vue'

const props = defineProps({
  mode: { type: String, default: 'login' }
})

const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMsg = ref('')
const { login } = useAuth()
const isRegister = computed(() => props.mode === 'register')
const emit = defineEmits(['login-success', 'register-success', 'go-register', 'go-login', 'go-privacy'])

async function handleSubmit() {
  if (!email.value || !password.value) {
    errorMsg.value = 'Por favor completa todos los campos'
    return
  }
  errorMsg.value = ''
  loading.value = true

  try {
    if (isRegister.value) {
      await handleRegister()
    } else {
      await handleLogin()
    }
  } finally {
    loading.value = false
  }
}

async function handleRegister() {
  const response = await fetch(API_URL + '/api/createUser', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: email.value, password: password.value })
  })

  if (!response.ok) {
    const data = await response.json()
    errorMsg.value = data.error || 'Error al crear la cuenta'
    return
  }

  emit('register-success')
}

async function handleLogin() {
  const response = await fetch(API_URL + '/api/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: email.value, password: password.value })
  })

  if (!response.ok) {
    errorMsg.value = 'Las credenciales proporcionadas no son correctas'
    return
  }

  const data = await response.json()
  login({ token: data.token })
  emit('login-success')
}
</script>

<template>
  <div class="login-backdrop">
    <div class="login-panel">
      <header class="login-header">
        <div class="login-ornament">
          <span class="login-ornament__line"></span>
          <span class="login-ornament__glyph">✦</span>
          <span class="login-ornament__line"></span>
        </div>
        <h2 class="login-title">{{ isRegister ? 'Registro' : 'Iniciar sesión' }}</h2>
      </header>

      <form class="login-form" @submit.prevent="handleSubmit">
        <Transition name="toast">
          <ErrorMessage :message="errorMsg" />
        </Transition>

        <div class="login-field">
          <label class="login-label" for="email">Correo</label>
          <input id="email" v-model="email" class="login-input" type="email" autocomplete="email" placeholder="tu@correo.com"/>
        </div>

        <div class="login-field">
          <label class="login-label" for="password">Contraseña</label>
          <input id="password" v-model="password" class="login-input" type="password" :autocomplete="isRegister ? 'new-password' : 'current-password'" placeholder="••••••••"/>
        </div>

        <button class="login-btn" type="submit" :disabled="loading" @click="">
          {{ loading ? (isRegister ? 'Creando cuenta...' : 'Entrando...') : (isRegister ? 'Crear cuenta' : 'Iniciar sesion') }}
        </button>
      </form>

      <footer class="login-footer">
        <button class="login-register-link" @click="emit(isRegister ? 'go-login' : 'go-register')">
          {{ isRegister ? 'Ya tengo cuenta' : 'Registrarse' }}
        </button>
      </footer>
    </div>
  </div>
</template>

<style scoped src="../styles/Login.css"></style>