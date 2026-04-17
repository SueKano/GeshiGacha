<script setup>
import { ref, onMounted } from 'vue'
import { RARITIES } from '../scripts/constants.js'
import { useAuth } from '../scripts/useAuth.js'
import ErrorMessage from "./ErrorMessage.vue"

const { isAuthenticated, doFetch } = useAuth()
const battles = ref([])
const loading = ref(true)
const error = ref(null)

onMounted(async () => {
  try {
    const response = await doFetch('/api/battleHistory')
    if (!response) return
    battles.value = await response.json()
  } catch {
    error.value = 'No se pudo cargar el historial'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="chronicle">
    <header class="chronicle-header">
      <h2 class="chronicle-title">{{ isAuthenticated ? 'Historial de Batallas' : 'Ultimas batallas realizadas' }}</h2>
    </header>
    <div v-if="loading" class="chronicle-state">
      <span class="chronicle-state__rune">✦</span>
      <p class="chronicle-state__text">Consultando el historial...</p>
    </div>

    <ErrorMessage :message="error" />
    <div v-if="battles.length === 0" class="chronicle-state">
      <span class="chronicle-state__scroll">📜</span>
      <p class="chronicle-state__text">Realiza batallas para que queden registradas aquí.</p>
    </div>

    <TransitionGroup v-else name="chronicle-list" tag="div" class="chronicle-entries">
      <article v-for="(battle, idx) in battles" :key="idx" class="chronicle-entry"
               :class="battle.isPlayerWon ? 'chronicle-entry--victory' : 'chronicle-entry--defeat'" :style="{ '--delay': `${idx * 55}ms` }">
        <div class="chronicle-entry__body">
          <div class="chronicle-clash">
            <div class="chronicle-fighter">
              <div class="chronicle-fighter__avatar" :style="{ '--rc': RARITIES[battle.userCard.rarity].color }">
                <img v-if="battle.userCard.urlImage" :src="battle.userCard.urlImage" :alt="battle.userCard.name" class="chronicle-fighter__img" />
                <span v-else>{{ battle.userCard.name.charAt(0) }}</span>
              </div>
              <div class="chronicle-fighter__info">
                <span class="chronicle-fighter__name">{{ battle.userCard.name }}</span>
                <span class="chronicle-fighter__meta">{{ battle.userCard.rarity }} · {{ battle.userCard.type }}</span>
              </div>
            </div>
            <span class="chronicle-clash__vs">VS</span>
            <div class="chronicle-fighter chronicle-fighter--right">
              <div class="chronicle-fighter__info chronicle-fighter__info--right">
                <span class="chronicle-fighter__name">{{ battle.enemyCard.name }}</span>
                <span class="chronicle-fighter__meta">{{ battle.enemyCard.rarity }} · {{ battle.enemyCard.type }}</span>
              </div>
              <div class="chronicle-fighter__avatar" :style="{ '--rc': RARITIES[battle.enemyCard.rarity].color }">
                <img v-if="battle.enemyCard.urlImage" :src="battle.enemyCard.urlImage" :alt="battle.enemyCard.name" class="chronicle-fighter__img" />
                <span v-else>{{ battle.enemyCard.name.charAt(0) }}</span>
              </div>
            </div>
          </div>
        </div>
        <footer class="chronicle-entry__verdict">
          <span class="chronicle-verdict" :class="battle.isPlayerWon ? 'chronicle-verdict--win' : 'chronicle-verdict--loss'">
            {{ battle.isPlayerWon ? 'Victoria' : 'Derrota' }}
          </span>
          <time class="chronicle-entry__date">{{ battle.battleDate }}</time>
        </footer>
      </article>
    </TransitionGroup>
  </section>
</template>

<style scoped src="../styles/BattleHistory.css"></style>