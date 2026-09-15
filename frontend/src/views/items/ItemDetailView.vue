<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
    <router-link to="/explore" class="inline-flex items-center gap-1.5 text-sm text-text-secondary hover:text-primary mb-6">
      <ArrowLeft class="w-4 h-4" /> Kembali ke jelajah
    </router-link>

    <div v-if="loading" class="card p-0 h-80 animate-pulse bg-slate-100" />
    <div v-else-if="!item" class="text-center py-20">
      <h1 class="text-xl font-semibold text-text-primary mb-2">Item ini sudah tidak tersedia.</h1>
      <router-link to="/explore" class="btn btn-primary mt-4">Ke Jelajah</router-link>
    </div>

    <template v-else>
      <div class="grid lg:grid-cols-2 gap-8">
        <!-- Left: image -->
        <div class="rounded-2xl overflow-hidden bg-slate-100 h-full min-h-80">
          <img v-if="item.image" :src="`/storage/${item.image}`" :alt="item.name" class="w-full h-full object-cover" />
          <div v-else class="w-full h-full min-h-80 flex items-center justify-center text-text-muted">
            <Image class="w-16 h-16" />
          </div>
        </div>

        <!-- Right: details -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <StatusBadge :status="item.status" />
            <StatusBadge v-if="item.moderation_status && item.moderation_status !== 'approved'" :status="item.moderation_status" />
            <span class="text-xs text-text-muted font-medium">{{ item.created_at }}</span>
          </div>
          <h1 class="text-3xl font-bold mb-1">{{ item.name }}</h1>
          <p class="text-text-muted mb-6">{{ item.category }} · dilaporkan oleh {{ item.user?.name || 'Anonim' }}</p>

          <div class="space-y-2.5 text-text-secondary mb-6">
            <p class="flex items-center gap-2">
              <MapPin class="w-4 h-4 text-text-muted" />
              {{ item.location }}
              <span v-if="item.storage_location" class="text-sm text-text-muted">· disimpan di: {{ item.storage_location }}</span>
            </p>
            <p class="flex items-center gap-2">
              <Calendar class="w-4 h-4 text-text-muted" /> {{ formatDate(item.date) }}
              <span v-if="item.time" class="text-sm text-text-muted">· sekitar {{ item.time }}</span>
            </p>
          </div>

          <div class="card p-5 mb-6">
            <h2 class="text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Deskripsi</h2>
            <p class="text-text-secondary leading-relaxed">{{ item.description || 'Tidak ada deskripsi.' }}</p>
          </div>

          <!-- Moderation notice -->
          <div v-if="item.moderation_status && item.moderation_status !== 'approved'" class="card p-4 mb-6 bg-amber-50 border border-warning/40 text-sm text-text-secondary">
            <p class="font-semibold text-warning mb-1">Laporan ini belum disetujui.</p>
            <p v-if="isAdmin">Laporan masih menunggu moderasi dan belum tampil publik. Setujui lewat menu <router-link to="/admin/reports" class="font-medium text-primary hover:underline">Moderasi Laporan</router-link>.</p>
            <p v-else>Laporanmu dalam antrean moderasi admin dan belum tampil di halaman publik.</p>
          </div>

          <!-- Owner actions -->
          <div v-if="isOwner" class="card p-5 mb-6">
            <h2 class="font-semibold mb-4">Kelola laporan ini</h2>
            <div class="space-y-3">
              <label class="label" for="status">Perbarui status</label>
              <div class="flex gap-2">
                <select v-model="newStatus" id="status" class="input">
                  <option value="lost">Hilang</option>
                  <option value="found">Ditemukan</option>
                  <option value="verified">Terverifikasi</option>
                  <option value="returned">Dikembalikan</option>
                </select>
                <button class="btn btn-primary shrink-0" @click="updateStatus">Simpan</button>
              </div>
              <button class="btn btn-danger w-full" @click="deleteItem">Hapus Laporan</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Possible matches -->
      <section v-if="item.matches?.length" class="mt-12">
        <div class="flex items-center gap-2 mb-5">
          <Sparkles class="w-5 h-5 text-warning" />
          <h2 class="text-2xl font-bold">Kemungkinan Cocok</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <router-link
            v-for="m in item.matches"
            :key="m.item.id"
            :to="`/items/${m.item.id}`"
            class="card p-4 card-hover flex items-center gap-4"
          >
            <div class="w-14 h-14 rounded-xl bg-slate-100 shrink-0 overflow-hidden">
              <img v-if="m.item.image" :src="`/storage/${m.item.image}`" :alt="m.item.name" class="w-full h-full object-cover" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-semibold truncate">{{ m.item.name }}</p>
              <p class="text-xs text-text-muted">{{ m.item.location }}</p>
              <div class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-emerald-50 text-success px-2 py-0.5 text-xs font-semibold">
                {{ m.score }}% cocok
              </div>
            </div>
          </router-link>
        </div>
      </section>

      <!-- Claim section -->
      <section class="mt-12" v-if="!isOwner">
        <div class="card p-6 max-w-xl">
          <template v-if="!isAuthed">
            <div class="text-center py-6">
              <p class="text-text-secondary mb-4">Login untuk mengajukan klaim barang ini.</p>
              <router-link :to="{ name: 'login', query: { redirect: $route.fullPath } }" class="btn btn-primary">Masuk</router-link>
            </div>
          </template>

          <template v-else-if="item.type === 'lost' || item.type === 'found'">
            <!-- already claimed / already returned -->
            <div v-if="myClaim || ['returned', 'verified'].includes(item.status)" class="text-center py-4">
              <CheckCircle2 v-if="myClaim?.status === 'approved' || item.status === 'returned'" class="w-10 h-10 text-success mx-auto mb-3" />
              <CheckCircle2 v-else class="w-10 h-10 text-blue-500 mx-auto mb-3" />
              <p class="font-semibold">
                {{ myClaim?.status === 'approved' || item.status === 'returned'
                    ? 'Barang ini telah dikembalikan.'
                    : myClaim ? `Klaimmu: ${claimLabel(myClaim.status)}` : 'Barang ini sedang diproses.' }}
              </p>
              <template v-if="myClaim?.status === 'pending'">
                <p class="text-sm text-text-muted mt-1">Menunggu verifikasi oleh admin.</p>
                <StatusBadge v-if="item.status" :status="item.status" />
              </template>
            </div>

            <div v-else-if="hasQuestions">
              <h2 class="text-xl font-bold mb-2">Klaim item ini</h2>
              <p class="text-sm text-text-secondary mb-5">Untuk membuktikan kepemilikan, jawab pertanyaan-pertanyaan ini dengan benar.</p>
              <form class="space-y-4" @submit.prevent="submitClaim">
                <div v-for="(q, i) in questions" :key="i">
                  <label class="label" :for="`ans-${i}`">{{ q.q }}</label>
                  <input v-model="answers[i]" :id="`ans-${i}`" type="text" class="input" required />
                </div>
                <p v-if="claimError" class="text-sm text-danger">{{ claimError }}</p>
                <button class="btn btn-primary w-full py-3" :disabled="claiming">
                  {{ claiming ? 'Mengirim...' : 'Kirim Klaim' }}
                </button>
              </form>
            </div>

            <div v-else class="text-center py-4">
              <p class="text-text-secondary mb-2">Barang ini tanpa pertanyaan verifikasi.</p>
              <button class="btn btn-primary" @click="submitClaim(true)">Klaim Item Ini</button>
            </div>
          </template>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ArrowLeft, Image, MapPin, Calendar, Sparkles, CheckCircle2 } from 'lucide-vue-next'
import { api, toast } from '../../api'
import { authStore } from '../../store/auth'
import { useRoute } from 'vue-router'
import StatusBadge from '../../components/ui/StatusBadge.vue'

const route = useRoute()
const item = ref(null)
const loading = ref(true)
const myClaim = ref(null)
const questions = ref([])
const answers = ref([])
const claimError = ref('')
const claiming = ref(false)
const newStatus = ref('')

const isAuthed = authStore.isAuthed
const user = authStore.user
const isOwner = computed(() => isAuthed.value && item.value && user.value?.id === item.value.user_id)
const isAdmin = authStore.isAdmin
const hasQuestions = computed(() => questions.value.length > 0)

function formatDate(str) {
  return new Date(str + 'T00:00:00').toLocaleDateString('id-ID', {
    year: 'numeric', month: 'long', day: 'numeric'
  })
}

function claimLabel(status) {
  return { pending: 'menunggu', approved: 'disetujui', rejected: 'ditolak' }[status] || status
}

async function load() {
  loading.value = true
  try {
    item.value = await api.get(`/api/items/${route.params.id}`)
    if (item.value.verification_answers) {
      questions.value = item.value.verification_answers
      answers.value = questions.value.map(() => '')
    }
   if (isAuthed.value && !isOwner.value) {
      try {
        myClaim.value = await api.get(`/api/items/${route.params.id}/claim`)
      } catch { myClaim.value = null }
    }
  } catch {
    item.value = null
  } finally {
    loading.value = false
  }
}

async function submitClaim(noQuestions = false) {
  claiming.value = true
  claimError.value = ''
  try {
    const payload = noQuestions
      ? { answers: [] }
      : { answers: questions.value.map((q, i) => ({ q: q.q, user_answer: answers.value[i] })) }
    const claim = await api.post(`/api/items/${item.value.id}/claims`, payload)
    myClaim.value = claim
    toast('Klaim terkirim! Menunggu verifikasi admin.')
  } catch (e) {
    claimError.value = e.message
  } finally {
    claiming.value = false
  }
}

async function updateStatus() {
  try {
    item.value = await api.put(`/api/items/${item.value.id}`, { status: newStatus.value })
    toast('Status diperbarui.')
  } catch (e) {
    toast(e.message, 'error')
  }
}

async function deleteItem() {
  if (!confirm('Hapus laporan ini?')) return
  try {
    await api.del(`/api/items/${item.value.id}`)
    toast('Laporan dihapus.')
    window.location.href = '/explore'
  } catch (e) {
    toast(e.message, 'error')
  }
}

onMounted(load)
</script>