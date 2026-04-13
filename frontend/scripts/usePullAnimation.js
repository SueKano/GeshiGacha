import { ref } from 'vue'
import { animate, createTimeline, stagger } from 'animejs'
import { RARITIES } from './constants.js'

export function usePullAnimation() {
  const phase = ref('idle')
  let glowAnimation = null
  let impactBounceAnimation = null
  let appShakeAnimation = null
  let ssrAnimations = []
  let activeTimelines = []
  let lastEls = null

  function stopAllAnimations() {
    glowAnimation?.pause(); glowAnimation = null
    impactBounceAnimation?.pause(); impactBounceAnimation = null
    appShakeAnimation?.pause(); appShakeAnimation = null
    ssrAnimations.forEach(a => a.pause()); ssrAnimations = []
    activeTimelines.forEach(tl => tl.pause()); activeTimelines = []
  }

  async function play(timeline) {
    activeTimelines.push(timeline)
    timeline.play()
    await timeline
  }

  function clearSSREffects(els) {
    for (const key of ['ssrRaysEl', 'ssrSpeedLinesEl', 'ssrGoldenFlashEl', 'ssrSilhouetteEl']) {
      if (els[key]) els[key].style.opacity = '0'
    }
  }

  function resetStyles(els) {
    const appEl = document.getElementById('app')
    if (appEl) appEl.style.transform = ''
    if (els.cardEl) {
      els.cardEl.style.opacity = '0'
      els.cardEl.style.transform = ''
      els.cardEl.style.boxShadow = ''
    }
    if (els.cardInnerEl) els.cardInnerEl.style.transform = ''
    clearSSREffects(els)
  }

  async function runIntro(els, isHighRarity) {
    phase.value = 'breaking'

    const sealTl = createTimeline({ autoplay: false })
    sealTl.add(els.sealEl, {
      rotate: [0, -5, 5, -3, 3, 0],
      duration: 400,
      ease: 'inOutQuad',
    })
    if (els.candleLeftEl && els.candleRightEl) {
      sealTl.add([els.candleLeftEl, els.candleRightEl], {
        scale: [1, 1.8],
        opacity: [0.8, 1],
        duration: 300,
        ease: 'outQuad',
      }, '-=200')
    }
    sealTl.add(els.sealEl, {
      scale: [1, 1.3, 0],
      opacity: [1, 1, 0],
      rotate: [0, 15],
      duration: 400,
      ease: 'inBack',
    })
    await play(sealTl)
    phase.value = 'unrolling'

    const scrollTl = createTimeline({ autoplay: false })
    scrollTl.add(els.scrollBodyEl, {
      maxHeight: [0, 500],
      opacity: [0, 1],
      duration: 600,
      ease: 'outQuart',
    })
    if (isHighRarity && els.lightBurstEl) {
      scrollTl.add(els.lightBurstEl, {
        scale: [0, 2.5],
        opacity: [0.6, 0],
        duration: 500,
        ease: 'outExpo',
      }, '-=300')
    }
    await play(scrollTl)
  }

  async function revealSSR(els, rarityConfig) {
    phase.value = 'flipping'
    const appEl = document.getElementById('app')
    const rayEls = els.ssrRaysEl ? Array.from(els.ssrRaysEl.children) : []
    const speedLineEls = els.ssrSpeedLinesEl ? Array.from(els.ssrSpeedLinesEl.children) : []

    const flashTl = createTimeline({ autoplay: false })
    flashTl.add(els.ssrGoldenFlashEl, {
      opacity: [0, 0.35, 0],
      duration: 600,
      ease: 'inOutSine',
    })
    await play(flashTl)

    const tensionTl = createTimeline({ autoplay: false })
    tensionTl.add(els.ssrSpeedLinesEl, {
      opacity: [0, 0.4],
      duration: 200,
      ease: 'outQuad',
    })
    if (speedLineEls.length > 0) {
      tensionTl.add(speedLineEls, {
        scaleX: [0, 1],
        opacity: [0, 0.3],
        duration: 400,
        delay: stagger(30),
        ease: 'outExpo',
      }, 0)
    }
    if (appEl) {
      tensionTl.add(appEl, {
        translateX: [0, -4, 5, -3, 4, -2, 1, 0],
        translateY: [0, 2, -3, 1, -2, 1, 0],
        duration: 500,
        ease: 'inOutQuad',
      }, 0)
    }
    await play(tensionTl)

    const raysTl = createTimeline({ autoplay: false })
    raysTl.add(els.ssrRaysEl, {
      opacity: [0, 0.7],
      duration: 200,
      ease: 'outQuad',
    })
    if (rayEls.length > 0) {
      raysTl.add(rayEls, {
        scaleY: [0, 1],
        opacity: [0, 0.5],
        duration: 500,
        delay: stagger(40, { from: 'center' }),
        ease: 'outExpo',
      }, 0)
    }
    await play(raysTl)

    const silhouetteTl = createTimeline({ autoplay: false })
    silhouetteTl.add(els.ssrSilhouetteEl, {
      opacity: [0, 0.8],
      duration: 400,
      ease: 'outQuad',
    })
    await play(silhouetteTl)

    ssrAnimations.push(
      animate(els.ssrGoldenFlashEl, { opacity: [0, 0.25, 0], duration: 400, ease: 'outQuad' }),
      animate(els.ssrSilhouetteEl, { opacity: [0.8, 0], duration: 300, ease: 'outQuad' }),
    )

    const cardTl = createTimeline({ autoplay: false })
    cardTl.add(els.cardEl, {
      translateY: [180, -10, 0],
      scale: [0.4, 1.02, 1],
      opacity: [0, 1, 1],
      duration: 900,
      ease: 'outQuart',
    })
    await play(cardTl)
    els.cardEl.style.opacity = ''

    if (appEl) {
      appShakeAnimation = animate(appEl, {
        translateX: [0, -2, 3, -1, 0],
        translateY: [0, 1, -2, 0],
        duration: 250,
        ease: 'inOutQuad',
      })
    }

    phase.value = 'revealed'
    impactBounceAnimation = animate(els.cardEl, {
      scale: [1, 1.08, 0.96, 1.03, 1],
      duration: 900,
      ease: 'outElastic(1, 0.5)',
    })

    ssrAnimations.push(
      animate(els.ssrRaysEl, { opacity: 0, duration: 1500, ease: 'outQuad' }),
      animate(els.ssrSpeedLinesEl, { opacity: 0, duration: 1000, ease: 'outQuad' }),
    )

    glowAnimation = animate(els.cardEl, {
      boxShadow: [
        { to: `0 0 30px ${rarityConfig.glowColorIntense}, 0 0 60px ${rarityConfig.glowColor}` },
        { to: `0 0 12px ${rarityConfig.glowColor}, 0 0 25px transparent` },
      ],
      duration: 1400,
      ease: 'inOutSine',
      loop: true,
      alternate: true,
    })

    if (els.particleEls?.length > 0) {
      animate(els.particleEls, {
        translateX: () => `${(Math.random() - 0.5) * 500}px`,
        translateY: () => `${(Math.random() - 0.5) * 500}px`,
        scale: [0, () => Math.random() * 2 + 0.5],
        opacity: [0.8, 0],
        rotate: () => Math.random() * 720,
        duration: () => 1200 + Math.random() * 600,
        delay: stagger(10, { from: 'center' }),
        ease: 'outExpo',
      })

      setTimeout(() => {
        if (!els.particleEls?.length) return
        animate(els.particleEls, {
          translateX: () => `${(Math.random() - 0.5) * 300}px`,
          translateY: () => `${-100 - Math.random() * 300}px`,
          scale: [0, () => Math.random() + 0.3],
          opacity: [0.6, 0],
          rotate: () => Math.random() * 360,
          duration: () => 1500 + Math.random() * 800,
          delay: stagger(15, { from: 'center' }),
          ease: 'outQuad',
        })
      }, 600)
    }
  }

  async function revealNormal(els, card, rarityConfig) {
    phase.value = 'flipping'

    const flipTl = createTimeline({ autoplay: false })
    flipTl.add(els.cardEl, {
      translateY: [60, 0],
      opacity: [0, 1],
      duration: 400,
      ease: 'outQuart',
    })
    await play(flipTl)
    els.cardEl.style.opacity = ''

    phase.value = 'revealed'
    animate(els.cardEl, {
      scale: [1, 1.04, 1],
      duration: 500,
      ease: 'outElastic(1, 0.6)',
    })

    if (card.rarity === 'SR' && els.cardEl) {
      glowAnimation = animate(els.cardEl, {
        boxShadow: [
          { to: `0 0 30px ${rarityConfig.glowColor}` },
          { to: `0 0 6px transparent` },
        ],
        duration: 1800,
        ease: 'inOutSine',
        loop: true,
        alternate: true,
      })
    }

    if (card.rarity === 'SR' && els.particleEls?.length > 0) {
      animate(els.particleEls, {
        translateX: () => `${(Math.random() - 0.5) * 180}px`,
        translateY: () => `${(Math.random() - 0.5) * 180}px`,
        scale: [0, () => Math.random() + 0.3],
        opacity: [0.7, 0],
        rotate: () => Math.random() * 360,
        duration: () => 600 + Math.random() * 300,
        delay: stagger(25, { from: 'center' }),
        ease: 'outQuad',
      })
    }
  }

  async function runPullSequence(els, card, { skipIntro = false } = {}) {
    lastEls = els
    const rarityConfig = RARITIES[card.rarity]
    const isHighRarity = card.rarity === 'SSR' || card.rarity === 'SR'

    stopAllAnimations()
    if (!skipIntro) await runIntro(els, isHighRarity)
    resetStyles(els)

    if (card.rarity === 'SSR') {
      await revealSSR(els, rarityConfig)
    } else {
      await revealNormal(els, card, rarityConfig)
    }

    if (!skipIntro && els.candleLeftEl && els.candleRightEl) {
      animate([els.candleLeftEl, els.candleRightEl], {
        scale: [1.8, 1],
        duration: 800,
        ease: 'outQuad',
      })
    }
  }

  function resetAnimation() {
    phase.value = 'idle'
    stopAllAnimations()
    const appEl = document.getElementById('app')
    if (appEl) appEl.style.transform = ''
    if (lastEls) {
      if (lastEls.sealEl) {
        lastEls.sealEl.style.opacity = ''
        lastEls.sealEl.style.transform = ''
      }
      if (lastEls.scrollBodyEl) {
        lastEls.scrollBodyEl.style.maxHeight = ''
        lastEls.scrollBodyEl.style.opacity = ''
      }
    }
  }

  return { phase, runPullSequence, resetAnimation }
}