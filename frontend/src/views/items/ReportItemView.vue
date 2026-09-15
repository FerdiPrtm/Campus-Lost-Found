<template>
  <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
    <router-link to="/explore" class="inline-flex items-center gap-1.5 text-sm text-text-secondary hover:text-primary mb-6">
      <ArrowLeft class="w-4 h-4" /> Kembali
    </router-link>

    <h1 class="text-3xl font-bold mb-2">
      {{ type === 'lost' ? 'Lapor Barang Hilang' : 'Lapor Barang Ditemukan' }}
    </h1>
    <p class="text-text-secondary mb-8">
      {{ type === 'lost'
        ? 'Semakin detail informasimu, semakin mudah barang ditemukan kembali.'
        : 'Terima kasih sudah menemukan barang! Bantu kami mengembalikannya ke pemilik.' }}
    </p>

    <form class="space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
      <!-- Basic information -->
      <section class="card p-6">
        <h2 class="font-semibold text-lg mb-5">Informasi Dasar</h2>
        <div class="space-y-4">
          <div>
            <label class="label" for="item-name">Nama Item <span class="text-danger">*</span></label>
            <input v-model="form.name" id="item-name" type="text" class="input" required placeholder="mis. AirPods Pro hitam" />
          </div>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="label" for="category">Kategori <span class="text-danger">*</span></label>
              <select v-model="form.category" id="category" class="input" required>
                <option value="" disabled>Pilih kategori</option>
                <option v-for="c in categories" :key="c.name" :value="c.name">{{ c.name }}</option>
              </select>
            </div>
            <div>
              <label class="label" for="location">{{ type === 'lost' ? 'Lokasi Terakhir Dilihat' : 'Lokasi Ditemukan' }}</label>
              <select v-model="form.location" id="location" class="input" required>
                <option value="" disabled>Pilih lokasi</option>
                <option v-for="l in locations" :key="l.name" :value="l.name">{{ l.name }}</option>
              </select>
            </div>
          </div>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="label" for="date">{{ type === 'lost' ? 'Tanggal Hilang' : 'Tanggal Ditemukan' }}</label>
              <input v-model="form.date" id="date" type="date" class="input" required :max="today" />
            </div>
            <div>
              <label class="label" for="time">{{ type === 'lost' ? 'Perkiraan Waktu' : 'Waktu Ditemukan' }}</label>
              <input v-model="form.time" id="time" type="time" class="input" />
            </div>
          </div>
          <div>
            <label class="label" for="description">Deskripsi</label>
            <textarea
              v-model="form.description"
              id="description"
              class="input min-h-24"
              rows="4"
              :placeholder="type === 'lost'
                ? 'mis. Case hitam dengan goresan kecil di bagian belakang...'
                : 'mis. Ditemukan di atas meja, masih ada earbud di dalamnya...'"
            />
          </div>
          <div v-if="type === 'found'">
            <label class="label" for="storage">Lokasi Penyimpanan Saat Ini</label>
            <input
              v-model="form.storage_location"
              id="storage"
              type="text"
              class="input"
              placeholder="mis. Kantor Keamanan"
            />
            <p class="text-xs text-text-muted mt-1.5">
              Di mana barang ini sekarang disimpan? Pemilik perlu tahu tanpa harus mencari ke lokasi awal.
            </p>
          </div>
        </div>
      </section>

      <!-- Photo -->
      <section class="card p-6">
        <h2 class="font-semibold text-lg mb-2">Foto</h2>
        <p class="text-sm text-text-secondary mb-4">Bantu pencocokan dengan foto yang jelas.</p>
        <label
          class="block border-2 border-dashed border-slate-300 rounded-2xl cursor-pointer hover:border-primary transition-colors overflow-hidden"
          :class="{ 'p-0': preview }"
        >
          <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="onFile" />
          <div v-if="!preview" class="p-10 text-center text-text-muted">
            <Upload class="w-10 h-10 mx-auto mb-2" />
            <p class="text-sm font-medium text-text-secondary">Klik untuk unggah (JPG/PNG, maks 5MB)</p>
          </div>
          <img v-else :src="preview" class="w-full h-64 object-cover" alt="Pratinjau item" />
        </label>
      </section>

      <!-- Verification questions -->
      <section class="card p-6">
        <h2 class="font-semibold text-lg mb-2">Kontak</h2>
        <p class="text-sm text-text-secondary mb-4">
          Email akunmu akan ditampilkan sebagai kontak saat laporan disetujui, sehingga pemilik/penemu
          bisa menghubungimu langsung. Ubah email lewat akunmu jika perlu.
        </p>
      </section>

      <p v-if="error" class="text-sm text-danger">{{ error }}</p>

      <div class="flex gap-3">
        <button class="btn btn-primary flex-1 py-3.5" :disabled="loading">
          {{ loading ? 'Menerbitkan...' : type === 'lost' ? 'Terbitkan Laporan Hilang' : 'Terbitkan Laporan Ditemukan' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ArrowLeft, Upload } from 'lucide-vue-next'
import { api, toast } from '../../api'
import { useRouter } from 'vue-router'

const props = defineProps({ type: { type: String, default: 'lost' } })
const router = useRouter()

const categories = ref([])
const locations = ref([])
const loading = ref(false)
const error = ref('')
const preview = ref('')
const file = ref(null)

const today = computed(() => new Date().toISOString().split('T')[0])

const form = reactive({
  name: '',
  category: '',
  description: '',
  date: '',
  time: '',
  location: '',
  storage_location: ''
})

function onFile(e) {
  const f = e.target.files[0]
  if (!f) return
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(f.type)) {
    error.value = 'Hanya gambar JPG, PNG, atau WebP yang diizinkan.'
    return
  }
  file.value = f
  preview.value = URL.createObjectURL(f)
}

async function submit() {
  loading.value = true
  error.value = ''
  try {
    const fd = new FormData()
    fd.append('type', props.type)
    for (const key of ['name', 'category', 'description', 'date', 'time', 'location', 'storage_location']) {
      if (form[key]) fd.append(key, form[key])
    }
    if (file.value) fd.append('image', file.value)

    const item = await api.post('/api/items', fd, { json: false })
    toast(props.type === 'lost' ? 'Laporan hilang diterbitkan!' : 'Laporan ditemukan diterbitkan!')
    router.push(`/items/${item.id}`)
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const [cats, locs] = await Promise.all([api.get('/api/categories'), api.get('/api/locations')])
    categories.value = cats
    locations.value = locs
  } catch {
    /* ignore */
  }
})
</script>