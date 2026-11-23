<template>
  <div class="care-wrap care-page care-stack">
    <section class="care-card care-grid items-center">
      <div class="care-stack">
        <span class="care-badge">Demonstratie met fictieve gegevens</span>
        <h1 class="care-title">Met vertrouwen zorg verlenen, ook thuis.</h1>
        <p class="text-lg">Bekijk je zorgplanning, doorloop een handeling en houd bij hoe het daarna gaat.</p>
        <div class="care-row"><NuxtLink to="/login" class="care-button">Open mijn zorgomgeving</NuxtLink></div>
      </div>
      <div class="care-card care-stack" style="background: var(--wash)">
        <h2 class="text-xl font-bold">Jouw zorgomgeving</h2>
        <div class="care-card"><strong>Vandaag</strong><p>Zie welke handelingen en controles gepland staan.</p></div>
        <div class="care-card"><strong>Stap voor stap</strong><p>Bekijk de uitleg op je eigen tempo.</p></div>
        <div class="care-card"><strong>Logboek</strong><p>Bekijk wat je hebt geregistreerd.</p></div>
      </div>
    </section>
  </div>
</template>
