import { ref, computed } from 'vue'
import { API_URL } from './api.js'

const savedToken = localStorage.getItem('token')
const currentUser = ref(savedToken ? { token: savedToken } : null)

export function useAuth() {
  function login(user) {
    currentUser.value = user
    localStorage.setItem('token', user.token)
  }

  function logout() {
    currentUser.value = null
    localStorage.removeItem('token')
  }

  const isAuthenticated = computed(() => !!currentUser.value)
  async function doFetch(url, options = {}) {
    if (isAuthenticated.value) {
      options.headers = {
        ...options.headers,
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    }
    const response = await fetch(API_URL + url, options)
    if (response.status === 401 || response.status === 403) {
      logout()
      return null
    }
    return response
  }

  return { currentUser, isAuthenticated, login, logout, doFetch }
}