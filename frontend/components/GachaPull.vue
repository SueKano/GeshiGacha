<script setup>
import { ref, computed, nextTick, onMounted } from 'vue'
import { useGachaPull } from '../scripts/useGachaPull.js'
import { usePullAnimation } from '../scripts/usePullAnimation.js'
import { useDailyPulls } from '../scripts/useDailyPulls.js'
import { useCounterToSSR } from '../scripts/useCounterToSSR.js'
import { RARITIES } from '../scripts/constants.js'
import Card from './Card.vue'
import ErrorMessage from './ErrorMessage.vue'
import { useAuth } from '../scripts/useAuth.js'

const { lastPulledCard, pullHistory, isPulling, pullCard, addToHistory } = useGachaPull()
const { phase, runPullSequence, resetAnimation } = usePullAnimation()
const { canPull, remainingPulls, maxPulls, consumePull, fetchServerPulls, serverLoading, serverCount, serverMaxCount } = useDailyPulls()
const { countToSSR, maxCountToSSR, isGuaranteed, incrementCount, resetCount } = useCounterToSSR()
const { isAuthenticated, doFetch } = useAuth()

const errorMsg = ref('')
const sealRef = ref(null)
const scrollRef = ref(null)
const scrollBodyRef = ref(null)
const candleLeftRef = ref(null)
const candleRightRef = ref(null)
const lightBurstRef = ref(null)
const cardRef = ref(null)
const particleContainerRef = ref(null)
const ssrGoldenFlashRef = ref(null)
const ssrRaysRef = ref(null)
const ssrSpeedLinesRef = ref(null)
const ssrSilhouetteRef = ref(null)
const pullCount = ref(0)
const gotSSRInSession = ref(false)
const sessionActive = ref(false)
const MAX_PULLS = 5

const displayCount = computed(() => {
  if (isAuthenticated.value && serverCount.value !== null) return Math.floor(serverCount.value / 5)
  return countToSSR.value
})
const displayMaxCount = computed(() => {
  if (isAuthenticated.value && serverMaxCount.value !== null) return Math.floor(serverMaxCount.value / 5)
  return maxCountToSSR
})

onMounted(() => {
  if (isAuthenticated.value) fetchServerPulls()
})

async function handlePull(nextCard = false) {
    if (!nextCard) {
      if (!canPull.value) return
      pullCount.value = 0
      gotSSRInSession.value = false
      sessionActive.value = true
      consumePull()
    } else {
      if (phase.value !== 'revealed') return
    }

  await getSinglePull()
  if (isAuthenticated.value) await fetchServerPulls()
  await nextTick()
}

async function getSinglePull() {
  if (pullCount.value >= MAX_PULLS) return
  if (cardRef.value) cardRef.value.style.opacity = '0'

  const forceRarity = (!isAuthenticated.value && isGuaranteed.value && pullCount.value === 0) ? 'SSR' : null
  const card = await pullCard(forceRarity)
  if (!card) {
    errorMsg.value = 'No se pudo obtener una carta. Inténtalo más tarde.'
    sessionActive.value = false
    return
  }
  errorMsg.value = ''
  if (card.rarity === 'SSR') gotSSRInSession.value = true

  if (isAuthenticated.value) {
    await doFetch('/api/addCollection', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ card: card })
    })
  }
  pullCount.value++
  const particleEls = particleContainerRef.value ? Array.from(particleContainerRef.value.children) : []
  const cardInnerEl = cardRef.value?.querySelector('.card__inner')

  await runPullSequence({
    sealEl: sealRef.value,
    scrollEl: scrollRef.value,
    scrollBodyEl: scrollBodyRef.value,
    candleLeftEl: candleLeftRef.value,
    candleRightEl: candleRightRef.value,
    lightBurstEl: lightBurstRef.value,
    cardEl: cardRef.value,
    cardInnerEl,
    particleEls,
    ssrGoldenFlashEl: ssrGoldenFlashRef.value,
    ssrRaysEl: ssrRaysRef.value,
    ssrSpeedLinesEl: ssrSpeedLinesRef.value,
    ssrSilhouetteEl: ssrSilhouetteRef.value,
  }, card, { skipIntro: pullCount.value > 1 })

  addToHistory(card)
}

function dismissCard() {
  if (phase.value !== 'revealed') return
  if (pullCount.value < MAX_PULLS) return

  if (!isAuthenticated.value) {
    if (gotSSRInSession.value) {
      resetCount()
    } else {
      incrementCount()
    }
  }
  sessionActive.value = false
  resetAnimation()
  lastPulledCard.value = null
}
</script>

<template>
  <section class="altar-scene">
    <header class="invocation-header">
      <div class="invocation-ornament"></div>
      <h2 class="invocation-title">Invocar</h2>
      <div class="invocation-ornament"></div>
    </header>

    <Transition name="toast">
      <ErrorMessage :message="errorMsg" />
    </Transition>

    <div class="pull-info">
      <div class="pity-counter">
        <span class="pity-label">SSR garantizado</span>
        <div class="pity-dots">
          <span v-for="i in displayMaxCount" :key="i" class="pity-dot" :class="{ 'pity-dot--filled': i <= displayCount }"></span>
        </div>
        <span class="pity-text">{{ displayCount }}/{{ displayMaxCount }}</span>
      </div>

      <div class="daily-pulls">
        <span class="daily-pulls-icon">&#9788;</span>
        <span v-if="serverLoading" class="daily-pulls-text">
          <span class="daily-pulls-label">Cargando...</span>
        </span>
        <span v-else class="daily-pulls-text">
          {{ remainingPulls }}/{{ maxPulls }}
          <span class="daily-pulls-label">invocaciones hoy</span>
        </span>
      </div>
    </div>

    <div class="altar" :class="{ 'altar--active': phase !== 'idle' }">
      <div class="candle candle--left">
        <div class="candle-glow"></div>
        <div ref="candleLeftRef" class="candle-flame"></div>
        <div class="candle-body"></div>
      </div>

      <div class="candle candle--right">
        <div class="candle-glow"></div>
        <div ref="candleRightRef" class="candle-flame"></div>
        <div class="candle-body"></div>
      </div>

      <div class="altar-stone">
        <div class="altar-stone-top"></div>
        <div class="altar-stone-base"></div>

        <div ref="scrollRef" class="scroll" v-show="phase === 'idle' || phase === 'breaking' || phase === 'unrolling'">
          <div class="scroll-roll scroll-roll--top"></div>
          <button ref="sealRef" class="seal" :class="{ 'seal--clickable': !isPulling, 'seal--disabled': !canPull }" :disabled="!canPull"
                  @click="handlePull(false)">
            <span class="seal-text">{{ canPull ? 'Abrir' : '—' }}</span>
          </button>
          <div class="scroll-roll scroll-roll--bottom"></div>
        </div>

        <Transition name="fade">
          <p v-if="phase === 'idle' && !isPulling && !sessionActive" class="altar-hint">
            {{ canPull ? 'Pulsa para invocar' : 'Vuelve mañana para invocar' }}
          </p>
        </Transition>
      </div>
    </div>

    <Transition name="fade">
      <div v-if="phase === 'flipping' || phase === 'revealed'" class="overlay" @click="dismissCard"></div>
    </Transition>

    <div v-show="phase === 'unrolling' || phase === 'flipping'" class="stage">
      <div ref="lightBurstRef" class="light-burst"></div>
    </div>

    <div ref="ssrGoldenFlashRef" class="ssr-golden-flash"></div>
    <div ref="ssrRaysRef" class="ssr-rays">
      <div v-for="i in 12" :key="'ray-'+i" class="ssr-ray-wrapper" :style="{ transform: `rotate(${i * 30}deg)` }">
        <div class="ssr-ray"></div>
      </div>
    </div>
    <div ref="ssrSpeedLinesRef" class="ssr-speed-lines">
      <div v-for="i in 20" :key="'sl-'+i" class="ssr-speed-line-wrapper" :style="{ transform: `rotate(${i * 18}deg)` }">
        <div class="ssr-speed-line"></div>
      </div>
    </div>
    <div ref="ssrSilhouetteRef" class="ssr-silhouette">
      <div class="ssr-silhouette-card"></div>
    </div>

    <div v-show="phase === 'flipping' || phase === 'revealed'" class="stage stage--card">
      <div ref="cardRef" class="card-wrapper">
        <Card v-if="lastPulledCard" :card="lastPulledCard" :interactive="false" @pull="handlePull(true)"/>
      </div>
    </div>

    <p v-if="phase === 'revealed' && pullCount < MAX_PULLS" class="dismiss-hint" @click="handlePull(true)">
      Pulsa para ver la siguiente carta ({{ pullCount }}/{{ MAX_PULLS }})
    </p>
    <p v-else-if="phase === 'revealed' && pullCount >= MAX_PULLS" class="dismiss-hint" @click="dismissCard">
      Toca para continuar
    </p>

    <section v-if="pullHistory.length > 0" class="history">
      <hr class="history-ornament" />
      <h4 class="history-title">Invocaciones Recientes</h4>
      <TransitionGroup name="list" tag="div" class="history-grid">
        <article v-for="(histCard, idx) in pullHistory.slice(0, 10)" :key="`${histCard.id}-${idx}-${pullHistory.length}`" class="history-item"
                 :style="{ '--r-color': RARITIES[histCard.rarity].color, '--r-glow': RARITIES[histCard.rarity].glowColor}">
          <span class="history-initial">{{ histCard.name.charAt(0) }}</span>
          <span class="history-name">{{ histCard.name.split(' ')[0] }}</span>
          <span class="history-rarity">{{ histCard.rarity }}</span>
        </article>
      </TransitionGroup>
    </section>
  </section>
</template>
<style scoped src="../styles/GachaPull.css"></style>