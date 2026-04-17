import { ref } from 'vue'
import {useAuth} from "./useAuth.js";

const { doFetch } = useAuth()

export function useGachaPull() {
  const lastPulledCard = ref(null)
  const pullHistory = ref([])
  const isPulling = ref(false)

  async function pullCard(forceRarity = null) {
    try {
      const url = forceRarity ? `/api/getCard?rarity=${forceRarity}` : '/api/getCard'
      const response = await doFetch(url)
      if (!response) return null
      const card = await response.json()
      lastPulledCard.value = card
      return card
    } catch {
    }
  }

  function addToHistory(card) {
    pullHistory.value.unshift(card)
  }

  return { lastPulledCard, pullHistory, isPulling, pullCard, addToHistory }
}