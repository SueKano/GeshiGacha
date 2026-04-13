<script setup>
import { computed } from 'vue'
import { RARITIES } from '../scripts/constants.js'
import { getWikipediaUrl } from '../scripts/wikipedia.js'
import WikipediaImage from './WikipediaImage.vue'

const props = defineProps({
  card: { type: Object, required: true },
  interactive: { type: Boolean, default: true },
})

const emit = defineEmits(['pull'])
const rarity = computed(() => RARITIES[props.card.rarity])
const wikipediaUrl = computed(() => getWikipediaUrl(props.card.name))
</script>

<template>
  <div class="card" :class="[`card--${card.rarity.toLowerCase()}`, {'card--interactive': interactive }]" :style="{
    '--rarity-color': rarity.color, '--rarity-glow': rarity.glowColor, '--rarity-glow-intense': rarity.glowColorIntense,
    '--rarity-gradient': rarity.borderGradient }">
    <div class="card__inner" @click="emit('pull')">
      <section class="card__face card__face--front">
        <div class="card__corners" aria-hidden="true"></div>
        <span class="card__rarity-badge">{{ card.rarity }}</span>

        <WikipediaImage :url-image="card.urlImage" :name="card.name" :href="wikipediaUrl" class="card__portrait" />
        <header class="card__info">
          <h3 class="card__name">{{ card.name }}</h3>
          <p class="card__title">{{ card.type }}</p>
          <p class="card__era">{{ card.age }}</p>
        </header>
        <hr class="card__divider" />
        <footer class="card__stats">
          <div class="card__stat">
            <span class="card__stat-label">Ataque</span>
            <div class="card__stat-bar">
              <div class="card__stat-fill" :style="{ width: `${card.attack}%` }"></div>
            </div>
            <span class="card__stat-value">{{ card.attack }}</span>
          </div>
          <div class="card__stat">
            <span class="card__stat-label">Defensa</span>
            <div class="card__stat-bar">
              <div class="card__stat-fill" :style="{ width: `${card.defense}%` }"></div>
            </div>
            <span class="card__stat-value">{{ card.defense }}</span>
          </div>
        </footer>
      </section>
    </div>
  </div>
</template>

<style scoped src="../styles/Card.css"></style>