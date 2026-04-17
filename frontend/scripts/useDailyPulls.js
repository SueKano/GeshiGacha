import { ref, computed } from 'vue'
import { useAuth } from './useAuth.js'

const STORAGE_KEY = 'daily_pulls'
const { isAuthenticated, doFetch } = useAuth()
const PULLS_PER_SESSION = 5

export function useDailyPulls() {
  const serverRemaining = ref(null)
  const serverMax = ref(null)
  const serverLoading = ref(isAuthenticated.value)
  const serverCount = ref(null)
  const serverMaxCount = ref(null)

  function getTodayStr() {
    return new Date().getDay()
  }

  function loadState() {
    const data = localStorage.getItem(STORAGE_KEY)
    if (data) {
      const parsed = JSON.parse(data)
      if (parsed.date === getTodayStr()) return parsed
    }
    return { date: getTodayStr(), used: 0 }
  }

  const state = ref(loadState())
  function saveState() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state.value))
  }

  async function getServerPulls() {
    serverLoading.value = true
    try {
      const response = await doFetch('/api/pullsRemaining')
      if (!response) return
      const data = await response.json()
      serverRemaining.value = data.remaining
      serverMax.value = data.max
      serverCount.value = data.count ?? null
      serverMaxCount.value = data.maxCount ?? null
    } catch {
      serverRemaining.value = null
      serverMax.value = null
      serverPity.value = null
      serverPityMax.value = null
    } finally {
      serverLoading.value = false
    }
  }

  const maxPulls = computed(() => {
    if (isAuthenticated.value && serverMax.value !== null) return serverMax.value / PULLS_PER_SESSION
    return new Date().getDay() === 0 ? 2 : 1
  })

  const remainingPulls = computed(() => {
    if (isAuthenticated.value && serverRemaining.value !== null) return serverRemaining.value / PULLS_PER_SESSION

    if (state.value.date !== getTodayStr()) {
      state.value = { date: getTodayStr(), used: 0 }
      saveState()
    }
    return Math.max(0, maxPulls.value - state.value.used)
  })

  const canPull = computed(() => !serverLoading.value && remainingPulls.value > 0)
  function consumePull() {
    if (isAuthenticated.value) return
    const today = getTodayStr()
    const used = state.value.date === today ? state.value.used : 0
    state.value = { date: today, used: used + 1 }
    saveState()
  }

  return { canPull, remainingPulls, maxPulls, consumePull, fetchServerPulls: getServerPulls, serverLoading, serverCount, serverMaxCount }
}