<script setup lang="ts">
import { practiceQuestions, practiceScore } from '~/data/carePractice'

definePageMeta({ middleware: 'auth' })

const answers = reactive<Record<string, string>>({})
const confidence = ref('')
const checked = ref(false)
const heading = ref<HTMLElement>()
const score = computed(() => practiceScore(answers))
const confidenceFeedback: Record<string, string> = {
  confident: 'Ga terug naar Mijn zorg om de route in de demo te proberen.',
  unsure: 'Lees de uitleg hieronder en start de oefening opnieuw wanneer je klaar bent.',
  help: 'Bekijk de extra uitleg via de knop Vragen? tijdens de demonstratie.',
}
const confidenceOptions: Record<string, string> = {
  confident: 'Ik kan dit onderdeel van de app zelf gebruiken',
  unsure: 'Ik wil het nog een keer oefenen',
  help: 'Ik zou graag uitleg krijgen',
}

async function showResults() {
  checked.value = true
  await nextTick()
  heading.value?.focus()
}

async function restart() {
  for (const question of practiceQuestions) delete answers[question.id]
  confidence.value = ''
  checked.value = false
  await nextTick()
  heading.value?.focus()
}
</script>

<template>
  <div class="care-wrap care-page care-stack max-w-3xl">
    <div><NuxtLink to="/dashboard" class="care-button care-secondary">Terug naar Mijn zorg</NuxtLink></div>
    <header class="care-stack">
      <span class="care-badge">Oefenen met de demo</span>
      <h1 class="care-title">Oefentoets</h1>
      <p class="text-lg">Oefen hoe je uitleg bekijkt, een uitvoering registreert en een nazorgcheck herkent.</p>
      <p>Een zorgprofessional beoordeelt je vaardigheid apart. Je oefenantwoorden blijven op dit scherm.</p>
    </header>

    <form v-if="!checked" class="care-card care-stack" @submit.prevent="showResults">
      <h2 ref="heading" tabindex="-1" class="text-2xl font-bold">Oefenvragen</h2>
      <CareQuestion v-for="question in practiceQuestions" :key="question.id" v-model="answers[question.id]"
        :name="question.id" :question="question.label" :options="question.options" />
      <CareQuestion v-model="confidence" name="confidence" question="Hoe zeker voel je je over het gebruik van de app?" :options="confidenceOptions" />
      <div><button class="care-button" type="submit">Bekijk antwoorden</button></div>
    </form>

    <section v-else class="care-card care-stack" aria-labelledby="result-title">
      <h2 id="result-title" ref="heading" tabindex="-1" class="text-2xl font-bold">{{ score }} van {{ practiceQuestions.length }} goed</h2>
      <p>{{ confidenceFeedback[confidence] }}</p>
      <article v-for="question in practiceQuestions" :key="question.id" class="care-card care-stack">
        <span class="care-badge">{{ answers[question.id] === question.correct ? 'Goed' : 'Bekijk de uitleg' }}</span>
        <h3 class="text-xl font-bold">{{ question.label }}</h3>
        <p>{{ question.feedback }}</p>
      </article>
      <div class="care-row">
        <button class="care-button care-secondary" type="button" @click="restart">Opnieuw oefenen</button>
        <NuxtLink to="/dashboard" class="care-button">Naar Mijn zorg</NuxtLink>
      </div>
    </section>
  </div>
</template>
