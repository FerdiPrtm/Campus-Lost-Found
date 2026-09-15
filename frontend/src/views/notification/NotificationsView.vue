<template>
  <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold">Notifikasi</h1>
        <p class="text-text-secondary mt-1">{{ unread }} belum dibaca</p>
      </div>
      <button v-if="unread" class="btn btn-secondary" @click="readAll">Tandai semua sudah dibaca</button>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="card h-20 animate-pulse bg-slate-100" />
    </div>
    <EmptyState v-else-if="!items.length" title="Tidak ada notifikasi" message="Kamu akan mendapat notifikasi saat ada kemungkinan cocok, pesan masuk, atau pembaruan laporanmu." />

    <div v-else class="space-y-2.5">
      <router-link
        v-for="n in items"
        :key="n.id"
        :to="n.reference_id ? `/items/${n.reference_id}` : '/notifications'"
        class="card p-4 flex items-start gap-4 card-hover"
        :class="{ 'bg-primary/[0.03]': !n.is_read }"
        @click="read(n.id)"
      >
        <span
          class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
          :class="iconClass(n.type)"
        >
          <BellRing v-if="n.type === 'possible_match'" class="w-5 h-5 text-warning" />
          <MessageSquare v-else-if="n.type === 'new_message'" class="w-5 h-5 text-primary" />
          <CheckCircle2 v-else-if="['claim_approved', 'item_returned'].includes(n.type)" class="w-5 h-5 text-success" />
          <XCircle v-else-if="n.type === 'claim_rejected'" class="w-5 h-5 text-danger" />
          <Info v-else class="w-5 h-5 text-primary" />
        </span>
        <div class="min-w-0 flex-1">
          <div class="flex items-center justify-between gap-2">
            <p class="font-semibold text-sm">{{ n.title }}</p>
            <span class="text-xs text-text-muted shrink-0">{{ timeAgo(n.created_at) }}</span>
          </div>
          <p class="text-sm text-text-secondary mt-0.5">{{ n.message }}</p>
        </div>
        <span v-if="!n.is_read" class="w-2 h-2 rounded-full bg-primary shrink-0 mt-2" />
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { BellRing, CheckCircle2, XCircle, Info, MessageSquare } from 'lucide-vue-next'
import { api } from '../../api'
import EmptyState from '../../components/ui/EmptyState.vue'

const items = ref([])
const unread = ref(0)
const loading = ref(true)

function iconClass(type) {
  if (type === 'possible_match') return 'bg-amber-50'
  if (type === 'new_message') return 'bg-primary/10'
  if (['claim_approved', 'item_returned'].includes(type)) return 'bg-emerald-50'
  if (type === 'claim_rejected') return 'bg-red-50'
  return 'bg-primary/10'
}

function timeAgo(str) {
  const diff = (Date.now() - new Date(str).getTime()) / 1000
  if (diff < 3600) return `${Math.max(1, Math.floor(diff / 60))}mnt`
  if (diff < 86400) return `${Math.floor(diff / 3600)}jam`
  if (diff < 604800) return `${Math.floor(diff / 86400)}hari`
  return new Date(str).toLocaleDateString('id-ID')
}

async function load() {
  loading.value = true
  try {
    const data = await api.get('/api/notifications')
    items.value = data.items
    unread.value = data.unread
  } finally {
    loading.value = false
  }
}

async function read(id) {
  const n = items.value.find((x) => x.id === id)
  if (n && !n.is_read) {
    n.is_read = 1
    unread.value = Math.max(0, unread.value - 1)
    api.post(`/api/notifications/${id}/read`, {}).catch(() => {})
  }
}

async function readAll() {
  await api.post('/api/notifications/read-all', {})
  load()
}

onMounted(load)
</script>