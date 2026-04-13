import { ref, computed } from 'vue'

const STORAGE_KEY = 'count_SSR'
const MAX_PULLS_TO_SSR = 5

export function useCounterToSSR() {
  function loadCount() {
    try {
      const data = localStorage.getItem(STORAGE_KEY)
      if (data) {
        const parsed = JSON.parse(data)
        if (typeof parsed.count === 'number') return parsed.count
      }
    } catch {}
    return 0
  }

  const countToSSR = ref(loadCount())
  const maxCountToSSR = MAX_PULLS_TO_SSR
  const isGuaranteed = computed(() => countToSSR.value >= maxCountToSSR)

  function save() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ count: countToSSR.value }))

  }
  function incrementCount() {
    countToSSR.value++
    save()
  }
  function resetCount() {
    countToSSR.value = 0
    save()
  }

  return { countToSSR: countToSSR, maxCountToSSR: maxCountToSSR, isGuaranteed, incrementPity: incrementCount, resetPity: resetCount }
}