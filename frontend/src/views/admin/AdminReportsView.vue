<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-3xl font-bold mb-6">Moderasi Laporan</h1>
    <AdminTabs />

    <div class="card p-4 mb-6 flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1">
        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
        <input v-model="q" class="input pl-10" placeholder="Cari item, pelapor..." @keyup.enter="load" />
      </div>
      <select v-model="filter" class="input sm:w-48" @change="load">
        <option value="">Semua status</option>
        <option value="pending">Menunggu</option>
        <option value="approved">Disetujui</option>
        <option value="rejected">Ditolak</option>
        <option value="suspended">Disembunyikan</option>
      </select>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="card h-24 animate-pulse bg-slate-100" />
    </div>
    <EmptyState v-else-if="!reports.length" title="Laporan tidak ditemukan" message="Tidak ada laporan yang cocok dengan filter." />

    <div v-else class="space-y-3">
      <div v-for="r in reports" :key="r.id" class="card p-5">
        <div class="flex flex-wrap items-start gap-4">
          <div class="w-16 h-16 rounded-xl bg-slate-100 shrink-0 overflow-hidden">
            <img v-if="r.image" :src="`/storage/${r.image}`" :alt="r.name" class="w-full h-full object-cover" />
          </div>
          <div class="flex-1 min-w-56">
            <div class="flex flex-wrap items-center gap-2 mb-1">
              <h3 class="font-semibold">{{ r.name }}</h3>
              <StatusBadge :status="r.moderation_status === 'approved' ? 'verified' : r.moderation_status" />
            </div>
            <p class="text-sm text-text-secondary">
              {{ ['lost', 'found'].includes(r.type) ? (r.type === 'lost' ? 'Hilang' : 'Ditemukan') : r.type }} · {{ r.category }} · {{ r.location }} —
              dilaporkan oleh <span class="font-medium">{{ r.reporter_name }}</span>
            </p>
            <p class="text-xs text-text-muted mt-1">{{ r.created_at }}</p>
            <p v-if="r.moderation_reason" class="text-xs text-danger mt-1">Alasan: {{ r.moderation_reason }}</p>
          </div>
          <div class="flex flex-wrap gap-2 shrink-0">
            <button v-if="r.moderation_status !== 'approved'" class="btn btn-primary px-3 py-2" @click="moderate(r, 'approve')">Setujui</button>
            <button v-if="r.moderation_status !== 'rejected'" class="btn btn-secondary px-3 py-2" @click="moderate(r, 'reject', true)">Tolak</button>
            <button v-if="r.moderation_status !== 'suspended'" class="btn btn-secondary px-3 py-2" @click="moderate(r, 'suspend', true)">Sembunyikan</button>
            <button class="btn btn-danger px-3 py-2" @click="moderate(r, 'delete', true)">Hapus</button>
            <router-link :to="`/items/${r.id}`" class="btn btn-secondary px-3 py-2">Lihat</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Search } from 'lucide-vue-next'
import { api, toast } from '../../api'
import AdminTabs from '../../components/ui/AdminTabs.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import EmptyState from '../../components/ui/EmptyState.vue'

const reports = ref([])
const q = ref('')
const filter = ref('')
const loading = ref(true)

async function load() {
  loading.value = true
  const params = new URLSearchParams({ q: q.value, moderation: filter.value })
  try {
    reports.value = await api.get(`/api/admin/reports?${params}`)
  } finally {
    loading.value = false
  }
}

async function moderate(r, action, askReason = false) {
  let reason = ''
  if (askReason) {
    reason = action === 'delete'
      ? (confirm('Hapus laporan ini permanen?') ? 'deleted' : '')
      : (prompt('Alasan (opsional):') || '')
    if (action === 'delete' && !reason) return
  }
  try {
    await api.post(`/api/admin/reports/${r.id}/moderate`, { action, reason })
    toast(action === 'delete' ? 'Laporan dihapus.' : 'Laporan berhasil diperbarui.')
    load()
  } catch (e) {
    toast(e.message, 'error')
  }
}

onMounted(load)
</script>