<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-3xl font-bold mb-6">Tinjau Klaim</h1>
    <AdminTabs />

    <div class="mb-6 flex gap-2">
      <button
        v-for="s in statuses"
        :key="s.value"
        class="btn px-4 py-2"
        :class="filter === s.value ? 'btn-primary' : 'btn-secondary'"
        @click="filter = s.value; load()"
      >
        {{ s.label }}
      </button>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="card h-32 animate-pulse bg-slate-100" />
    </div>
    <EmptyState v-else-if="!claims.length" title="Tidak ada klaim di sini" />

    <div v-else class="space-y-4">
      <div v-for="c in claims" :key="c.id" class="card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
          <div>
            <h3 class="font-semibold">{{ c.claimant_name }} mengklaim "{{ c.item_name }}"</h3>
            <p class="text-xs text-text-muted mt-0.5 mb-3">Pemilik: {{ c.owner_name }} · {{ c.created_at }}</p>
            <StatusBadge :status="c.status" />
          </div>
          <div v-if="c.status === 'pending'" class="flex gap-2">
            <button class="btn btn-primary" @click="review(c, 'approve')">Setujui</button>
            <button class="btn btn-danger" @click="review(c, 'reject')">Tolak</button>
          </div>
        </div>

        <div class="bg-slate-50 rounded-xl p-4 space-y-2">
          <p class="text-xs font-semibold text-text-muted uppercase tracking-wide mb-2">Jawaban Verifikasi</p>
          <div v-for="(a, i) in c.answers" :key="i" class="flex items-start gap-2 text-sm">
            <CheckCircle2 v-if="isCorrect(c, a)" class="w-4 h-4 text-success mt-0.5 shrink-0" />
            <XCircle v-else class="w-4 h-4 text-danger mt-0.5 shrink-0" />
            <div>
              <p class="font-medium">{{ a.q }}</p>
              <p class="text-text-secondary">Jawaban: {{ a.user_answer }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { CheckCircle2, XCircle } from 'lucide-vue-next'
import { api, toast } from '../../api'
import AdminTabs from '../../components/ui/AdminTabs.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import EmptyState from '../../components/ui/EmptyState.vue'

const statuses = [
  { label: 'Menunggu', value: 'pending' },
  { label: 'Disetujui', value: 'approved' },
  { label: 'Ditolak', value: 'rejected' },
  { label: 'Semua', value: '' }
]
const claims = ref([])
const filter = ref('pending')
const loading = ref(true)

// correct answers aren't exposed to admin list, compare against item's answers
const correctByItem = new Map()

async function load() {
  loading.value = true
  try {
    claims.value = await api.get(`/api/claims?status=${filter.value}`)
  } finally {
    loading.value = false
  }
}

function isCorrect(claim, a) {
  return !!a.correct
}

async function review(c, action) {
  if (!confirm(`Setujui/tolak klaim oleh ${c.claimant_name}?`)) return
  try {
    await api.post(`/api/claims/${c.id}/${action}`, {})
    toast(`Klaim ${action === 'approve' ? 'disetujui' : 'ditolak'}.`)
    load()
  } catch (e) {
    toast(e.message, 'error')
  }
}

onMounted(load)
</script>