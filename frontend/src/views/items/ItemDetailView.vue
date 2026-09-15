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
            <div class="space-y-4">
              <div>
                <label class="label" for="status">Perbarui status</label>
                <div class="flex gap-2">
                  <select v-model="newStatus" id="status" class="input">
                    <option value="lost">Hilang</option>
                    <option value="found">Ditemukan</option>
                    <option value="returned">Dikembalikan</option>
                  </select>
                  <button class="btn btn-primary shrink-0" @click="updateStatus">Simpan</button>
                </div>
              </div>

              <button class="btn w-full" @click="editing ? (editing = false) : openEdit()">
                {{ editing ? 'Tutup Edit' : 'Edit Informasi' }}
              </button>
              <form v-if="editing" class="space-y-3 border-t border-slate-100 pt-4" @submit.prevent="saveEdit">
                <div>
                  <label class="label" for="e-name">Nama Item</label>
                  <input v-model="editForm.name" id="e-name" type="text" class="input" required />
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                  <div>
                    <label class="label" for="e-category">Kategori</label>
                    <select v-model="editForm.category" id="e-category" class="input" required>
                      <option v-for="c in categories" :key="c.name" :value="c.name">{{ c.name }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="label" for="e-location">Lokasi</label>
                    <select v-model="editForm.location" id="e-location" class="input" required>
                      <option v-for="l in locations" :key="l.name" :value="l.name">{{ l.name }}</option>
                    </select>
                  </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                  <div>
                    <label class="label" for="e-date">Tanggal</label>
                    <input v-model="editForm.date" id="e-date" type="date" class="input" required :max="today" />
                  </div>
                  <div>
                    <label class="label" for="e-time">Waktu</label>
                    <input v-model="editForm.time" id="e-time" type="time" class="input" />
                  </div>
                </div>
                <div v-if="item.type === 'found'">
                  <label class="label" for="e-storage">Lokasi Penyimpanan</label>
                  <input v-model="editForm.storage_location" id="e-storage" type="text" class="input" />
                </div>
                <div>
                  <label class="label" for="e-desc">Deskripsi</label>
                  <textarea v-model="editForm.description" id="e-desc" class="input min-h-24" rows="3"></textarea>
                </div>
                <p v-if="editError" class="text-sm text-danger">{{ editError }}</p>
                <button class="btn btn-primary w-full" :disabled="savingEdit">
                  {{ savingEdit ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
              </form>

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

      <!-- Contact -->
      <section v-if="isOwner" class="mt-12">
        <div class="card p-6 max-w-xl text-center">
          <p class="text-text-secondary">
            Ini laporanmu. Siapa pun yang login bisa melihat laporan ini dan
            memulai chat untuk menembalikan/menjumpakan barang.
          </p>
        </div>
      </section>
      <section v-else-if="item.contact_email" class="mt-12">
        <div class="card p-6 max-w-xl text-center">
          <template v-if="item.status === 'returned'">
            <CheckCircle2 class="w-10 h-10 text-success mx-auto mb-3" />
            <p class="font-semibold">Barang ini telah ditandai dikembalikan.</p>
            <p class="text-sm text-text-muted mt-1">Laporan tetap tampil sebagai riwayat.</p>
          </template>
          <template v-else>
            <h2 class="text-xl font-bold mb-2">
              {{ item.type === 'found' ? 'Kamu pemilik barang ini?' : 'Kamu menemukan barang ini?' }}
            </h2>
            <p class="text-sm text-text-secondary mb-5">
              {{ item.type === 'found'
                  ? 'Hubungi penemu untuk mengatur penyerahan barang.'
                  : 'Hubungi pemilik untuk mengembalikan barang ini.' }}
            </p>

            <template v-if="isAuthed">
              <button class="btn btn-primary w-full py-3" @click="startChat">
                <MessageCircle class="w-4 h-4" /> Chat dengan {{ item.type === 'found' ? 'Penemu' : 'Pemilik' }}
              </button>
              <a
                class="inline-block mt-3 text-xs text-text-muted hover:text-primary underline"
                :href="`mailto:${item.contact_email}?subject=${encodeURIComponent(`Barang ${item.name} di Campus Lost & Found`)}`"
              >
                atau hubungi via email: {{ item.contact_email }}
              </a>
            </template>
            <template v-else>
              <router-link
                class="btn btn-primary w-full py-3"
                :to="{ name: 'login', query: { redirect: $route.fullPath } }"
              >
                Masuk untuk chat
              </router-link>
              <a
                class="inline-block mt-3 text-xs text-text-muted hover:text-primary underline"
                :href="`mailto:${item.contact_email}?subject=${encodeURIComponent(`Barang ${item.name} di Campus Lost & Found`)}`"
              >
                atau hubungi via email: {{ item.contact_email }}
              </a>
            </template>
          </template>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ArrowLeft, Image, MapPin, Calendar, Sparkles, CheckCircle2, MessageCircle } from 'lucide-vue-next'
import { api, toast } from '../../api'
import { authStore } from '../../store/auth'
import { useRoute, useRouter } from 'vue-router'
import StatusBadge from '../../components/ui/StatusBadge.vue'

const route = useRoute()
const router = useRouter()
const item = ref(null)
const loading = ref(true)
const newStatus = ref('')
const editing = ref(false)
const savingEdit = ref(false)
const editError = ref('')
const categories = ref([])
const locations = ref([])
const editForm = ref({ name: '', category: '', location: '', date: '', time: '', storage_location: '', description: '' })

const isAuthed = authStore.isAuthed
const user = authStore.user
const isOwner = computed(() => isAuthed.value && item.value && user.value?.id === item.value.user_id)
const isAdmin = authStore.isAdmin
const today = computed(() => new Date().toISOString().split('T')[0])

function formatDate(str) {
  return new Date(str + 'T00:00:00').toLocaleDateString('id-ID', {
    year: 'numeric', month: 'long', day: 'numeric'
  })
}

async function load() {
  loading.value = true
  try {
    item.value = await api.get(`/api/items/${route.params.id}`)
    const [cats, locs] = await Promise.all([api.get('/api/categories'), api.get('/api/locations')])
    categories.value = cats
    locations.value = locs
  } catch {
    item.value = null
  } finally {
    loading.value = false
  }
}

function openEdit() {
  const i = item.value
  editForm.value = {
    name: i.name,
    category: i.category,
    location: i.location,
    date: i.date,
    time: i.time || '',
    storage_location: i.storage_location || '',
    description: i.description || ''
  }
  editing.value = true
  editError.value = ''
}

async function saveEdit() {
  savingEdit.value = true
  editError.value = ''
  try {
    item.value = await api.put(`/api/items/${item.value.id}`, editForm.value)
    editing.value = false
    toast('Laporan diperbarui.')
  } catch (e) {
    editError.value = e.message
  } finally {
    savingEdit.value = false
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

function startChat() {
  router.push({ name: 'messages', query: { item: item.value.id, with: item.value.user_id } })
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