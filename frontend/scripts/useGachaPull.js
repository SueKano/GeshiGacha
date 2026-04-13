import { ref } from 'vue'
import {useAuth} from "./useAuth.js";

const { authFetch } = useAuth()

export function useGachaPull() {
  const lastPulledCard = ref(null)
  const pullHistory = ref([])
  const isPulling = ref(false)

  async function pullCard() {
    try {
      const response = await authFetch('/api/getCard')
      if (!response) return null
      const card = await response.json()
      lastPulledCard.value = card
      return card
    } catch(err) {
      console.log(err.message)
    }
  }

  function addToHistory(card) {
    pullHistory.value.unshift(card)
  }

  return { lastPulledCard, pullHistory, isPulling, pullCard, addToHistory }
}