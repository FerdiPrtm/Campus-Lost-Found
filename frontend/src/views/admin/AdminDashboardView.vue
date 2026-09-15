<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold">Dasbor Admin</h1>
        <p class="text-text-secondary mt-1">Ringkasan aktivitas platform.</p>
      </div>
      <router-link to="/admin/reports" class="btn btn-primary">Moderasi Laporan</router-link>
    </div>

    <div v-if="loading" class="grid md:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="card h-24 animate-pulse bg-slate-100" />
    </div>

    <template v-else>
      <!-- Stat cards -->
      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-10">
        <div v-for="card in statCards" :key="card.label" class="card p-5">
          <p class="text-2xl font-bold text-text-primary mb-1">{{ card.value }}</p>
          <p class="text-xs text-text-secondary">{{ card.label }}</p>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid lg:grid-cols-2 gap-6 mb-8">
        <div class="card p-6">
          <h2 class="font-semibold mb-4">Hilang & Ditemukan per Bulan</h2>
          <canvas ref="monthCanvas" height="220" />
        </div>
        <div class="card p-6">
          <h2 class="font-semibold mb-4">Keberhasilan Dikembalikan</h2>
          <canvas ref="pieCanvas" height="220" />
        </div>
      </div>

      <div class="grid lg:grid-cols-2 gap-6">
        <div class="card p-6">
          <h2 class="font-semibold mb-4">Kategori Terbanyak Hilang</h2>
          <canvas ref="catCanvas" height="220" />
        </div>
        <div class="card p-6">
          <h2 class="font-semibold mb-4">Lokasi Teratas</h2>
          <canvas ref="locCanvas" height="220" />
        </div>
      </div>

      <!-- Recent reports -->
      <div class="card p-6 mt-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold">Laporan baru</h2>
          <router-link to="/admin/reports" class="text-sm text-primary font-medium hover:underline">Lihat semua</router-link>
        </div>
        <div v-if="!data.recent_reports?.length" class="text-sm text-text-muted py-6 text-center">
          Belum ada laporan.
        </div>
        <div v-else class="divide-y divide-slate-100">
          <router-link
            v-for="r in data.recent_reports"
            :key="r.id"
            :to="`/items/${r.id}`"
            class="flex items-center gap-3 py-3 card-hover"
          >
            <div class="min-w-0 flex-1">
              <p class="font-semibold truncate">{{ r.name }}</p>
              <p class="text-xs text-text-muted">{{ r.type === 'lost' ? 'Hilang' : 'Ditemukan' }} · {{ r.reporter_name }} · {{ r.created_at }}</p>
            </div>
            <StatusBadge :status="r.moderation_status === 'approved' ? 'verified' : r.moderation_status" />
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Chart, registerables } from 'chart.js'
import { api } from '../../api'
import StatusBadge from '../../components/ui/StatusBadge.vue'

Chart.register(...registerables)

const loading = ref(true)
const data = ref(null)
const monthCanvas = ref(null)
const pieCanvas = ref(null)
const catCanvas = ref(null)
const locCanvas = ref(null)

const charts = []

const statCards = computed(() => {
  const s = data.value?.stats || {}
  return [
    { label: 'Total Laporan', value: s.total_reports || 0 },
    { label: 'Item Hilang', value: s.lost_items || 0 },
    { label: 'Item Ditemukan', value: s.found_items || 0 },
    { label: 'Dikembalikan', value: s.returned_items || 0 },
    { label: 'Laporan Menunggu', value: s.pending_reports || 0 },
    { label: 'Pengguna', value: s.total_users || 0 }
  ]
})

onMounted(async () => {
  data.value = await api.get('/api/admin/stats')
  loading.value = false

  const byMonth = data.value.by_month.reverse()
  const months = byMonth.map((m) => m.month)
  charts.push(new Chart(monthCanvas.value, {
    type: 'line',
    data: {
      labels: months,
      datasets: [
        { label: 'Lost', data: byMonth.filter((m) => m.type === 'lost').map((m) => m.n), borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,0.08)', tension: 0.35, fill: true },
        { label: 'Found', data: byMonth.filter((m) => m.type === 'found').map((m) => m.n), borderColor: '#F59E0B', backgroundColor: 'rgba(245,158,11,0.08)', tension: 0.35, fill: true }
      ]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
  }))

  const rate = data.value.return_rate
  charts.push(new Chart(pieCanvas.value, {
    type: 'doughnut',
    data: {
      labels: ['Dikembalikan', 'Belum'],
      datasets: [{ data: [rate, 100 - rate], backgroundColor: ['#16A34A', '#E2E8F0'] }]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
  }))

  charts.push(new Chart(catCanvas.value, {
    type: 'bar',
    data: {
      labels: data.value.by_category.map((c) => c.category),
      datasets: [{ label: 'Item hilang', data: data.value.by_category.map((c) => c.n), backgroundColor: '#4F46E5' }]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
  }))

  charts.push(new Chart(locCanvas.value, {
    type: 'bar',
    data: {
      labels: data.value.by_location.map((l) => l.location),
      datasets: [{ label: 'Laporan', data: data.value.by_location.map((l) => l.n), backgroundColor: '#64748B' }]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
  }))
})

onUnmounted(() => charts.forEach((c) => c.destroy()))
</script>