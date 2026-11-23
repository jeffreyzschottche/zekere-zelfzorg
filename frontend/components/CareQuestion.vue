<script setup lang="ts">
defineProps<{ question: string; name: string; options: Record<string, string> }>()
const answer = defineModel<string>()
</script>

<template>
  <fieldset class="care-question">
    <legend class="font-semibold">{{ question }}</legend>
    <label v-for="(label, value) in options" :key="value">
      <input v-model="answer" type="radio" :name="name" :value="value" required />
      {{ label }}
    </label>
  </fieldset>
</template>
