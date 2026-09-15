<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
    <div class="mb-8">
      <h1 class="text-3xl font-bold">Selamat {{ greeting }} 👋</h1>
      <p class="text-text-secondary mt-1">Lacak barang hilang dan temuanmu.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-3 sm:gap-4 mb-10">
      <div v-for="s in statCards" :key="s.label" class="card p-4 sm:p-5 text-center">
        <p class="text-2xl sm:text-3xl font-bold text-text-primary">{{ s.value }}</p>
        <p class="text-sm text-text-secondary mt-1">{{ s.label }}</p>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
      <!-- My reports -->
      <section class="lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">Laporanku</h2>
          <span class="text-xs text-text-muted font-medium">{{ stats?.total || 0 }} total</span>
        </div>

        <div v-if="loading" class="space-y-3">
          <div v-for="i in 3" :key="i" class="card h-20 animate-pulse bg-slate-100" />
        </div>
        <EmptyState v-else-if="!reports.length" title="Belum ada laporan" message="Mulai lapor barang yang hilang atau ditemukan." actionText="Lapor Barang" @action="$router.push('/report/lost')" />

        <div v-else class="space-y-3">
          <router-link
            v-for="r in reports"
            :key="r.id"
            :to="`/items/${r.id}`"
            class="card p-4 flex items-center gap-4 card-hover"
          >
            <div class="w-12 h-12 rounded-xl bg-slate-100 shrink-0 overflow-hidden">
              <img v-if="r.image" :src="`/storage/${r.image}`" :alt="r.name" class="w-full h-full object-cover" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-semibold truncate">{{ r.name }}</p>
              <p class="text-xs text-text-muted">{{ r.location }} · {{ timeAgo(r.created_at) }}</p>
            </div>
            <StatusBadge :status="r.status" />
          </router-link>
        </div>

        </section>

      <!-- Side -->
      <aside class="space-y-8">
        <section>
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold">Notifikasi</h2>
            <router-link to="/notifications" class="text-sm text-primary font-medium hover:underline">Lihat semua</router-link>
          </div>
          <div v-if="!recentNotifications.length" class="text-sm text-text-muted card p-4">Belum ada notifikasi.</div>
          <ul v-else class="card divide-y divide-slate-100">
            <li v-for="n in recentNotifications.slice(0, 3)" :key="n.id" class="first:rounded-t-2xl last:rounded-b-2xl">
              <router-link
                :to="n.reference_id ? `/items/${n.reference_id}` : '/notifications'"
                class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50"
              >
                <span class="w-8 h-8 rounded-lg bg-slate-100 shrink-0 flex items-center justify-center">
                  <MessageSquare v-if="n.type === 'new_message'" class="w-4 h-4 text-primary" />
                  <Sparkles v-else-if="n.type === 'possible_match'" class="w-4 h-4 text-warning" />
                  <Info v-else class="w-4 h-4 text-primary" />
                </span>
                <span class="min-w-0 flex-1">
                  <p class="text-sm font-medium truncate">{{ n.title }}</p>
                  <p class="text-xs text-text-muted truncate">{{ n.message }}</p>
                </span>
                <span v-if="!n.is_read" class="w-2 h-2 rounded-full bg-primary shrink-0 mt-2" />
              </router-link>
            </li>
          </ul>
        </section>

        <section>
          <h2 class="text-lg font-bold mb-4">Aksi Cepat</h2>
          <div class="space-y-3">
            <router-link to="/report/lost" class="btn btn-primary w-full">Lapor Barang Hilang</router-link>
            <router-link to="/report/found" class="btn btn-secondary w-full">Lapor Barang Ditemukan</router-link>
            <router-link to="/explore" class="btn btn-secondary w-full">Jelajahi Item</router-link>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '../../api'
import { authStore } from '../../store/auth'
import EmptyState from '../../components/ui/EmptyState.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import { MessageSquare, Sparkles, Info } from 'lucide-vue-next'

const stats = ref(null)
const reports = ref([])
const recentNotifications = ref([])
const loading = ref(true)

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'pagi'
  if (h < 18) return 'siang'
  return 'malam'
})

const statCards = computed(() => {
  const s = stats.value || {}
  return [
    { label: 'Hilang', value: s.lost || 0 },
    { label: 'Ditemukan', value: s.found || 0 },
    { label: 'Dikembalikan', value: s.returned || 0 }
  ]
})

function timeAgo(str) {
  const diff = (Date.now() - new Date(str).getTime()) / 1000
  if (diff < 3600) return `${Math.max(1, Math.floor(diff / 60))} mnt lalu`
  if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`
  if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`
  return new Date(str).toLocaleDateString('id-ID')
}

onMounted(async () => {
  try {
    const data = await api.get('/api/dashboard')
    stats.value = { ...data.stats, total: data.reports.length }
    reports.value = data.reports
    recentNotifications.value = data.recent_notifications
  } catch {
    reports.value = []
  } finally {
    loading.value = false
  }
})
</script>