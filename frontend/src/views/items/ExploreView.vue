<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-3xl font-bold mb-2">Jelajahi Item</h1>
    <p class="text-text-secondary mb-8">Cari segala laporan barang hilang dan ditemukan di kampus.</p>

    <!-- Search -->
    <div class="relative max-w-2xl mb-6">
      <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
      <input
        v-model="query"
        class="input pl-12 py-3.5"
        placeholder="Cari item, deskripsi, kategori, lokasi..."
        aria-label="Cari item"
        @keyup.enter="load()"
      />
    </div>

    <!-- Filters -->
    <div class="card p-5 mb-8">
      <div class="grid gap-4 md:grid-cols-4">
        <div>
          <label class="label" for="f-status">Status</label>
          <select v-model="filters.status" id="f-status" class="input" @change="applyFilters">
            <option value="">Semua status</option>
            <option value="lost">Hilang</option>
            <option value="found">Ditemukan</option>
            <option value="claimed">Diklaim</option>
            <option value="verified">Terverifikasi</option>
            <option value="returned">Dikembalikan</option>
          </select>
        </div>
        <div>
          <label class="label" for="f-type">Tipe</label>
          <select v-model="filters.type" id="f-type" class="input" @change="applyFilters">
            <option value="">Semua tipe</option>
            <option value="lost">Barang Hilang</option>
            <option value="found">Barang Ditemukan</option>
          </select>
        </div>
        <div>
          <label class="label" for="f-category">Kategori</label>
          <select v-model="filters.category" id="f-category" class="input" @change="applyFilters">
            <option value="">Semua kategori</option>
            <option v-for="c in categories" :key="c.name" :value="c.name">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="label" for="f-location">Lokasi</label>
          <select v-model="filters.location" id="f-location" class="input" @change="applyFilters">
            <option value="">Semua lokasi</option>
            <option v-for="l in locations" :key="l.name" :value="l.name">{{ l.name }}</option>
          </select>
        </div>
      </div>
      <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <select v-model="filters.sort" class="input w-auto" @change="applyFilters" aria-label="Urutkan item">
          <option value="newest">Terbaru</option>
          <option value="oldest">Terlama</option>
          <option value="recently_updated">Terbaru Diperbarui</option>
        </select>
        <button v-if="hasActiveFilters" class="text-sm text-primary font-medium hover:underline" @click="resetFilters">
          Bersihkan Filter
        </button>
      </div>
    </div>

    <!-- Results -->
    <div v-if="loading" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <div v-for="i in 8" :key="i" class="card h-72 animate-pulse bg-slate-100" />
    </div>

    <EmptyState v-else-if="items.length === 0" @action="resetFilters" />

    <div v-else>
      <p class="text-sm text-text-muted mb-4">{{ pagination.total }} item ditemukan</p>
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <ItemCard v-for="item in items" :key="item.id" :item="item" />
      </div>

      <div v-if="pagination.total_pages > 1" class="flex items-center justify-center gap-2 mt-10">
        <button class="btn btn-secondary px-3 py-2" :disabled="page <= 1" @click="page--">←</button>
        <span class="text-sm text-text-secondary px-2">{{ page }} / {{ pagination.total_pages }}</span>
        <button class="btn btn-secondary px-3 py-2" :disabled="page >= pagination.total_pages" @click="page++">→</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { Search } from 'lucide-vue-next'
import { api } from '../../api'
import { useRoute, useRouter } from 'vue-router'
import ItemCard from '../../components/items/ItemCard.vue'
import EmptyState from '../../components/ui/EmptyState.vue'

const route = useRoute()
const router = useRouter()

const query = ref(route.query.q || '')
const categories = ref([])
const locations = ref([])
const items = ref([])
const pagination = ref({ total: 0, page: 1, total_pages: 1 })
const page = ref(parseInt(route.query.page) || 1)
const loading = ref(true)

const filters = ref({
  status: route.query.status || '',
  type: route.query.type || '',
  category: route.query.category || '',
  location: route.query.location || '',
  sort: route.query.sort || 'newest'
})

const hasActiveFilters = () =>
  query.value || Object.values(filters.value).some((v) => v !== '' && v !== 'newest')

async function load() {
  loading.value = true
  const params = new URLSearchParams({
    q: query.value,
    ...filters.value,
    page: page.value
  })
  router.replace({ path: '/explore', query: Object.fromEntries(params) })
  try {
    const data = await api.get(`/api/items?${params}`)
    items.value = data.items
    pagination.value = data.pagination
  } catch (e) {
    items.value = []
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  page.value = 1
  load()
}

function resetFilters() {
  query.value = ''
  filters.value = { status: '', type: '', category: '', location: '', sort: 'newest' }
  page.value = 1
  load()
}

watch([page, () => route.query.q], load)

onMounted(async () => {
  try {
    const [cats, locs] = await Promise.all([api.get('/api/categories'), api.get('/api/locations')])
    categories.value = cats
    locations.value = locs
  } finally {
    load()
  }
})
</script>