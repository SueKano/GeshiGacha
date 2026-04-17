<script setup>
import { computed, ref } from 'vue'
import { RARITIES, CARD_TYPES } from '../scripts/constants.js'
import { useAuth } from '../scripts/useAuth.js'
import { API_URL } from '../scripts/api.js'
import Card from "./Card.vue"
import ErrorMessage from "./ErrorMessage.vue"
import BattleHistory from "./BattleHistory.vue"
import { getWikipediaUrl } from '../scripts/wikipedia.js'

const { isAuthenticated, doFetch } = useAuth()
const playerCardRef = ref(null)
const enemyCardRef = ref(null)

const loading = ref(false)
const result = ref(null)
const error = ref(null)
const battleFinished = ref(false)
const playerWon = ref(null)
const choosingCard = ref(false)
const userCards = ref([])

const playerHealth = ref(100)
const enemyHealth = ref(100)
const playerMaxHealth = ref(1)
const enemyMaxHealth = ref(1)
const playerShield = ref(0)
const enemyShield = ref(0)
const playerMaxShield = ref(0)
const enemyMaxShield = ref(0)

const turns = ref([])
const currentTurnIndex = ref(-1)
const waitingForClick = ref(false)
const lastTurn = ref(null)
const turnPhase = ref('idle')
const battleEnvironment = ref(null)

const playerHealthPercent = computed(() => Math.max(0, (playerHealth.value / playerMaxHealth.value) * 100))
const enemyHealthPercent = computed(() => Math.max(0, (enemyHealth.value / enemyMaxHealth.value) * 100))
const playerShieldPercent = computed(() => playerMaxShield.value > 0 ? Math.max(0, (playerShield.value / playerMaxShield.value) * 100) : 0)
const enemyShieldPercent = computed(() => enemyMaxShield.value > 0 ? Math.max(0, (enemyShield.value / enemyMaxShield.value) * 100) : 0)
function reset() {
  result.value = null
  error.value = null
  battleFinished.value = false
  playerWon.value = null
  choosingCard.value = false
  userCards.value = []
  loading.value = false
  turns.value = []
  currentTurnIndex.value = -1
  waitingForClick.value = false
  lastTurn.value = null
  turnPhase.value = 'idle'
  battleEnvironment.value = null
  playerShield.value = 0
  enemyShield.value = 0
  playerMaxShield.value = 0
  enemyMaxShield.value = 0
}

function healthColorClass(percent) {
  if (percent > 50) return 'health-bar__fill--high'
  if (percent > 25) return 'health-bar__fill--mid'
  return 'health-bar__fill--low'
}

function initBattleStats(data) {
  playerMaxHealth.value = data.playerHealth
  enemyMaxHealth.value = data.enemyHealth
  playerHealth.value = data.playerHealth
  enemyHealth.value = data.enemyHealth
  playerMaxShield.value = data.playerShield
  enemyMaxShield.value = data.enemyShield
  playerShield.value = data.playerShield
  enemyShield.value = data.enemyShield
}

function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms))
}

async function playTurns(turns) {
  for (const turn of turns) {
    await delay(800)
    playerHealth.value = turn.playerHealth
    enemyHealth.value = turn.enemyHealth
    playerShield.value = turn.playerShield
    enemyShield.value = turn.enemyShield
  }
}

function advanceTurn() {
  if (!waitingForClick.value || turnPhase.value !== 'idle') return

  currentTurnIndex.value++
  const turn = turns.value[currentTurnIndex.value]
  lastTurn.value = turn

  turnPhase.value = 'player_attack'
  enemyHealth.value = turn.enemyHealth
  enemyShield.value = turn.enemyShield

  if (turn.playerAttack >= 0 && enemyCardRef.value) {
    enemyCardRef.value.classList.add('battle-card--hit')
    setTimeout(() => enemyCardRef.value?.classList.remove('battle-card--hit'), 400)
  }

  if (turn.playerHeal > 0 && playerShield.value <= 0) {
    playerHealth.value = Math.min(playerHealth.value + turn.playerHeal, playerMaxHealth.value)
  }

  const playerPhaseDelay = (turn.playerHeal > 0 && playerShield.value <= 0) ? 1200 : 700
  setTimeout(() => {
    turnPhase.value = 'enemy_attack'
    playerHealth.value = turn.playerHealth
    playerShield.value = turn.playerShield

    if (turn.enemyAttack > 0 && playerCardRef.value) {
      playerCardRef.value.classList.add('battle-card--hit')
      setTimeout(() => playerCardRef.value?.classList.remove('battle-card--hit'), 400)
    }

    setTimeout(() => {
      turnPhase.value = 'idle'
      if (currentTurnIndex.value >= turns.value.length - 1) {
        waitingForClick.value = false
        playerWon.value = turn.playerHealth > 0
        battleFinished.value = true
        saveBattleHistory()
      }
    }, 700)
  }, playerPhaseDelay)
}

async function saveBattleHistory() {
  try {
    await doFetch('/api/addBattleHistory', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        playerCard: result.value.playerCard.name,
        enemyCard: result.value.enemyCard.name,
        result: playerWon.value,
      })
    })
  } catch {
    error.value = 'No se pudo conectar con el servidor'
  }
}
async function startNormalBattle() {
  if (!isAuthenticated.value) {
    error.value = 'Inicia sesión para acceder a este modo.'
    return
  }
  const environmentResponse = await doFetch('/api/getEnvironment')
  if (!environmentResponse) return
  battleEnvironment.value = await environmentResponse.json()

  choosingCard.value = true
  error.value = null
  try {
    const response = await doFetch('/api/collection')
    if (!response) return
    userCards.value = await response.json()
  } catch (err) {
    error.value = 'No se pudo conectar con el servidor'
    choosingCard.value = false
  }
}

async function doBattle(card) {
  choosingCard.value = false
  loading.value = true
  error.value = null
  battleFinished.value = false
  playerWon.value = null

  try {
    const enemyResponse = await doFetch('/api/enemyCard')
    if (!enemyResponse) return
    const enemyCard = await enemyResponse.json()

    const battleResponse = await doFetch('/api/battle', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        playerCard: card,
        enemyCard: enemyCard,
        environment: battleEnvironment.value
      }),
    })
    if (!battleResponse) return
    const battleData = await battleResponse.json()

    initBattleStats(battleData)
    result.value = { playerCard: card, enemyCard: enemyCard }
    loading.value = false
    turns.value = battleData.turns
    currentTurnIndex.value = -1
    waitingForClick.value = true
  } catch (err) {
    error.value = 'No se pudo conectar con el servidor'
    loading.value = false
  }
}

async function startRandomBattle() {
  loading.value = true
  error.value = null
  battleFinished.value = false
  playerWon.value = null

  try {
    const response = await fetch(API_URL + '/api/randomBattle')
    const data = await response.json()
    initBattleStats(data)

    result.value = {
      playerCard: data.playerCard,
      enemyCard: data.enemyCard,
    }

    loading.value = false
    await playTurns(data.turns)
    playerWon.value = data.winner.name === data.playerCard.name
    battleFinished.value = true
    await saveBattleHistory()

  } catch (err) {
    error.value = 'No se pudo conectar con el servidor'
    loading.value = false
  }
}
</script>

<template>
  <div class="battle-scene">
    <div v-if="!result && !loading && !choosingCard" class="battle-panel">
      <ErrorMessage :message="error" />
      <div class="battle-modes">
        <div class="battle-mode battle-mode--normal" @click="startNormalBattle">
          <span class="battle-mode__name">Batalla Normal</span>
          <p class="battle-mode__desc">Elige tu carta y combate contra un rival que cambia cada dia</p>
        </div>
        <div class="battle-modes__sep"></div>
        <div class="battle-mode battle-mode--random" @click="startRandomBattle">
          <span class="battle-mode__name">Batalla Aleatoria</span>
          <p class="battle-mode__desc">Dos cartas aleatorias luchan entre si </p>
        </div>
      </div>
    </div>

    <div v-if="!result && !loading && !choosingCard" class="chronicle-panel">
      <BattleHistory />
    </div>

    <div v-if="choosingCard" class="card-picker">
      <section v-if="battleEnvironment" class="scenario-hero" :style="{ '--ec': CARD_TYPES[battleEnvironment.affectedType].color }">
        <div class="scenario-hero__media">
          <img v-if="battleEnvironment.urlImage" :src="battleEnvironment.urlImage" :alt="battleEnvironment.name" class="scenario-hero__img" />
          <div v-else class="scenario-hero__placeholder" aria-hidden="true">
            <span>{{ battleEnvironment.name.charAt(0) }}</span>
          </div>
          <div class="scenario-hero__vignette" aria-hidden="true"></div>
          <a :href="getWikipediaUrl(battleEnvironment.name)" target="_blank" rel="noopener" class="scenario-hero__wiki">
            <span>Wikipedia</span>
            <span class="scenario-hero__wiki-arrow" aria-hidden="true">&#8599;</span>
          </a>
          <span class="scenario-hero__eyebrow">
            <span class="scenario-hero__eyebrow-text">Escenario del día</span>
          </span>
        </div>
        <div class="scenario-hero__body">
          <h3 class="scenario-hero__title">{{ battleEnvironment.name }}</h3>
          <p class="scenario-hero__desc">{{ battleEnvironment.description }}</p>
          <div class="scenario-hero__effect">
            <span class="scenario-hero__effect-type">{{ battleEnvironment.affectedType }}</span>
          </div>
        </div>
      </section>
      <h3 class="card-picker__title">Elige tu carta</h3>
      <div class="card-picker__grid">
        <article v-for="userCard in userCards" :key="userCard.id" class="card-picker__item" :style="{
          '--rc': RARITIES[userCard.card.rarity].color, '--rg': RARITIES[userCard.card.rarity].glowColorIntense, '--tc': CARD_TYPES[userCard.card.type].color }"
          @click="doBattle(userCard.card)">
          <div class="card-picker__portrait">
            <span class="card-picker__initial">{{ userCard.card.name.charAt(0) }}</span>
          </div>
          <span class="card-picker__name">{{ userCard.card.name }}</span>
          <div class="card-picker__badges">
            <span class="card-picker__rarity">{{ userCard.card.rarity }}</span>
            <span class="card-picker__type">{{ userCard.card.type }}</span>
          </div>
          <div class="card-picker__stats">
            <span class="card-picker__stat card-picker__stat--atk">{{ userCard.card.attack }}</span>
            <span class="card-picker__stat card-picker__stat--def">{{ userCard.card.defense }}</span>
          </div>
        </article>
      </div>
      <button class="battle-btn battle-btn--back" @click="choosingCard = false">Volver</button>
    </div>
    <p v-if="loading" class="battle-loading">Preparando batalla...</p>
    <div v-if="result" class="battle-arena">
      <div ref="playerCardRef" class="battle-card-wrapper" :class="{ 'battle-card--loser': battleFinished && !playerWon }">
        <span v-if="battleFinished" class="battle-label" :class="playerWon ? 'battle-label--winner' : 'battle-label--loser'">
            {{ playerWon ? 'GANADOR' : 'PERDEDOR' }}
        </span>
        <span v-if="lastTurn && lastTurn.enemyAttack >= 0 && turnPhase === 'enemy_attack'" :key="'pd' + currentTurnIndex" class="damage-number">
          -{{ lastTurn.enemyAttack }}
        </span>
        <span v-if="lastTurn && lastTurn.playerHeal > 0 && playerShield <= 0 && turnPhase === 'player_attack'" :key="'ph' + currentTurnIndex" class="heal-number">
          +{{ lastTurn.playerHeal }}
        </span>
        <div class="health-bar">
          <div class="health-bar__fill" :class="healthColorClass(playerHealthPercent)" :style="{ width: playerHealthPercent + '%' }"></div>
          <span class="health-bar__text">{{ playerHealth }}</span>
        </div>
        <div v-if="playerShield > 0" class="shield-bar">
          <div class="shield-bar__fill" :style="{ width: playerShieldPercent + '%' }"></div>
          <span class="shield-bar__text">{{ playerShield }}</span>
        </div>
        <Card :card="result.playerCard" :interactive="false" :in-battle="true" />
      </div>
      <span class="battle-vs">VS</span>
      <div ref="enemyCardRef" class="battle-card-wrapper" :class="{ 'battle-card--loser': battleFinished && playerWon }">
        <span v-if="battleFinished" class="battle-label" :class="!playerWon ? 'battle-label--winner' : 'battle-label--loser'">
          {{ !playerWon ? 'GANADOR' : 'PERDEDOR' }}
        </span>
        <span v-if="lastTurn && lastTurn.playerAttack >= 0 && turnPhase === 'player_attack'" :key="'ed' + currentTurnIndex" class="damage-number">
          -{{ lastTurn.playerAttack }}
        </span>
        <span v-if="lastTurn && lastTurn.enemyHeal > 0 && enemyShield <= 0 && turnPhase === 'enemy_attack'" :key="'eh' + currentTurnIndex" class="heal-number">
          +{{ lastTurn.enemyHeal }}
        </span>
        <div class="health-bar">
          <div class="health-bar__fill" :class="healthColorClass(enemyHealthPercent)" :style="{ width: enemyHealthPercent + '%' }"></div>
          <span class="health-bar__text">{{ enemyHealth }}</span>
        </div>
        <div v-if="enemyShield > 0" class="shield-bar">
          <div class="shield-bar__fill" :style="{ width: enemyShieldPercent + '%' }"></div>
          <span class="shield-bar__text">{{ enemyShield }} </span>
        </div>
        <Card :card="result.enemyCard" :interactive="false" :in-battle="true" />
      </div>
    </div>
    <div v-if="waitingForClick" class="battle-controls">
      <button v-if="turnPhase === 'idle'" class="battle-btn battle-btn--attack" @click="advanceTurn">⚔ Atacar</button>
    </div>
    <button class="battle-btn battle-btn--back" v-if="battleFinished" @click="reset">Volver</button>
  </div>
</template>

<style scoped src="../styles/Battle.css"></style>