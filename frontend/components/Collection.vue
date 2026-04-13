<script setup>
import { ref, computed, onMounted } from 'vue'
import { RARITIES } from '../scripts/constants.js'
import { useAuth } from '../scripts/useAuth.js'
import { getWikipediaUrl } from '../scripts/wikipedia.js'
import WikipediaImage from './WikipediaImage.vue'

const userCards = ref([])
const loading = ref(false)
const activeFilter = ref('ALL')
const initialLoad = ref(true)
const { isAuthenticated, authFetch } = useAuth()

onMounted(async () => {
  if (!isAuthenticated.value) return
  loading.value = true
  try {
    const response = await authFetch('/api/collection')
    if (!response) return
    userCards.value = await response.json()
  } finally {
    loading.value = false
    setTimeout(() => { initialLoad.value = false }, 600)
  }
})

const filters = ['ALL', 'SSR', 'SR', 'R']
const filtered = computed(() => {
  if (activeFilter.value === 'ALL') return userCards.value
  return userCards.value.filter(userCard => userCard.card.rarity === activeFilter.value)
})

function matchesFilter(userCard) {
  return activeFilter.value === 'ALL' || userCard.card.rarity === activeFilter.value
}

const counts = computed(() => {
  const cards = { ALL: userCards.value.length, SSR: 0, SR: 0, R: 0 }

  userCards.value.forEach(userCards => {
    const rarity = userCards.card.rarity
    cards[rarity]++
  })
  return cards
})

function getColorRarity(rarity) {
  return RARITIES[rarity]
}
</script>

<template>
  <div class="collection">
    <nav class="filters">
      <button v-for="filter in filters" :key="filter" class="filter-btn" :class="{ 'filter-btn--active': activeFilter === filter }" @click="activeFilter = filter">
        {{ filter === 'ALL' ? 'Todas' : filter }}
        <span class="filter-count">{{ counts[filter] }}</span>
      </button>
    </nav>

    <div v-if="!isAuthenticated" class="state-empty">
      <div class="empty-scroll">🔒</div>
      <p class="empty-message">Inicia sesión para poder guardar cartas en tu colección.</p>
    </div>

    <div v-else-if="loading" class="state-loading">
      <div class="loading-rune">✦</div>
      <p>Cargando tu colección...</p>
    </div>

    <div v-else-if="filtered.length === 0" class="state-empty">
      <div class="empty-scroll">📜</div>
      <p class="empty-message">
        {{ activeFilter === 'ALL' ? 'Tu colección está vacía.' : `No tienes ninguna carta ${activeFilter}.` }}
      </p>
    </div>

    <div v-else class="grid">
      <article v-for="(userCard, idx) in userCards" v-show="matchesFilter(userCard)" :key="userCard.id" class="plaque"
               :class="[`plaque--${userCard.card.rarity.toLowerCase()}`, { 'plaque--stagger': initialLoad }]"
               :style="{ '--rc': RARITIES[userCard.card.rarity].color, '--rg': RARITIES[userCard.card.rarity].glowColorIntense,
               '--rgrad': RARITIES[userCard.card.rarity].gradient, '--delay': `${idx * 40}ms`}">
        <div class="plaque__inner">
          <div v-if="userCard.card.rarity === 'SSR'" class="plaque__shimmer"></div>
          <span class="plaque__badge">{{ userCard.card.rarity }}</span>
          <WikipediaImage :url-image="userCard.card.urlImage" :name="userCard.card.name" :href="getWikipediaUrl(userCard.card.name)" class="plaque__portrait" />
          <div class="plaque__info">
            <h3 class="plaque__name">{{ userCard.card.name }}</h3>
            <p class="plaque__type">{{ userCard.card.type }}</p>
          </div>

          <div class="plaque__stats">
            <div class="plaque__stat">
              <span class="plaque__stat-icon">ATK </span>
              <div class="plaque__bar">
                <div class="plaque__bar-fill" :style="{ width: `${Math.min(100, userCard.card.attack ?? 0)}%` }"></div>
              </div>
              <span class="plaque__stat-val">{{ userCard.card.attack }}</span>
            </div>
            <div class="plaque__stat">
              <span class="plaque__stat-icon">DEF </span>
              <div class="plaque__bar">
                <div class="plaque__bar-fill" :style="{ width: `${Math.min(100, userCard.card.defense ?? 0)}%` }"></div>
              </div>
              <span class="plaque__stat-val">{{ userCard.card.defense }}</span>
            </div>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<style scoped src="../styles/Collection.css"></style>