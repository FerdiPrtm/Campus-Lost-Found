<template>
  <nav class="card p-1.5 mb-8 inline-flex flex-wrap gap-1" aria-label="Admin navigation">
    <router-link
      v-for="tab in tabs"
      :key="tab.to"
      :to="tab.to"
      class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
      active-class="bg-primary text-white"
      exact-active-class="bg-primary text-white"
    >
      {{ tab.label }}
    </router-link>
  </nav>
</template>

<script setup>
const tabs = [
  { label: 'Ringkasan', to: '/admin' },
  { label: 'Laporan', to: '/admin/reports' },
  { label: 'Klaim', to: '/admin/claims' },
  { label: 'Pengguna', to: '/admin/users' }
]
</script>