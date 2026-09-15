<template>
  <div>
    <!-- Hero -->
    <section class="relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br from-primary to-indigo-700" />
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-20 md:py-28 text-center text-white">
        <p class="text-primary-200 font-medium mb-3">Barang hilang? Temukan kembali.</p>
        <h1 class="text-4xl md:text-6xl font-bold leading-tight mb-4">
          Kehilangan barang di kampus?<br />
          <span class="text-indigo-200">Ayo bantu temukan kembali.</span>
        </h1>
        <p class="max-w-xl mx-auto text-indigo-100 text-lg mb-8">
          Lapor, cari, cocokkan, dan klaim kembali barangmu — semua dalam satu platform kampus.
        </p>

        <div class="max-w-lg mx-auto mb-8">
          <form class="relative" @submit.prevent="goSearch">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
            <input
              v-model="searchQuery"
              class="w-full rounded-2xl pl-12 pr-4 py-4 text-text-primary bg-white shadow-xl focus:outline-none focus:ring-2 focus:ring-white/60"
              placeholder="Cari barang hilang..."
              aria-label="Cari barang hilang"
            />
          </form>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
          <router-link to="/report/lost" class="w-full sm:w-auto btn px-6 py-3.5 bg-white text-primary hover:bg-indigo-50">
            Lapor Barang Hilang
          </router-link>
          <router-link to="/report/found" class="w-full sm:w-auto btn px-6 py-3.5 bg-primary hover:bg-indigo-500 text-white border border-white/30">
            Lapor Barang Ditemukan
          </router-link>
        </div>
      </div>
    </section>

    <!-- Stats -->
    <section class="max-w-5xl mx-auto px-4 sm:px-6 -mt-10 relative z-10">
      <div class="card p-6 md:p-8 grid grid-cols-2 md:grid-cols-4 gap-6 md:shadow-lg">
        <div v-for="s in stats" :key="s.label" class="text-center">
          <p class="text-3xl font-bold text-text-primary">{{ s.value.toLocaleString() }}</p>
          <p class="text-sm text-text-secondary mt-1">{{ s.label }}</p>
        </div>
      </div>
    </section>

    <!-- How it works -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
      <h2 class="text-3xl font-bold text-center mb-2">Cara kerjanya</h2>
      <p class="text-text-secondary text-center mb-12">Dari hilang sampai kembali, alurnya jelas.</p>
      <div class="grid md:grid-cols-3 gap-6">
        <div v-for="(step, i) in steps" :key="i" class="card p-6 card-hover">
          <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold mb-4">
            {{ i + 1 }}
          </div>
          <h3 class="font-semibold mb-2">{{ step.title }}</h3>
          <p class="text-sm text-text-secondary">{{ step.desc }}</p>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section class="bg-white border-y border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
        <h2 class="text-3xl font-bold text-center mb-12">Jelajahi berdasarkan kategori</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            v-for="c in categories"
            :key="c.name"
            :to="{ path: '/explore', query: { category: c.name } }"
            class="card p-5 text-center card-hover"
          >
            <MapIcon class="w-6 h-6 mx-auto mb-2 text-primary" />
            <p class="text-sm font-semibold">{{ c.name }}</p>
          </router-link>
        </div>
      </div>
    </section>

    <!-- Recent + possible matches -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
      <div class="flex items-center justify-between mb-8">
        <h2 class="text-3xl font-bold">Laporan terbaru</h2>
        <router-link to="/explore" class="btn btn-secondary">Jelajahi Semua</router-link>
      </div>
      <div v-if="loading" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div v-for="i in 4" :key="i" class="card p-0 h-72 animate-pulse bg-slate-100" />
      </div>
      <div v-else class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <ItemCard v-for="item in recent" :key="item.id" :item="item" />
      </div>
    </section>

    <!-- CTA -->
    <section class="bg-gradient-to-br from-primary to-indigo-700">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 py-16 text-center text-white">
        <h2 class="text-3xl font-bold mb-3">Bergabung dengan komunitas barang hilang kampus</h2>
        <p class="text-indigo-100 mb-8">Lapor barangmu sekarang — semakin cepat dilaporkan, semakin besar peluang kembali.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <router-link to="/register" class="btn px-6 py-3 bg-white text-primary">Buat Akun Gratis</router-link>
          <router-link to="/explore" class="btn px-6 py-3 bg-primary text-white border border-white/30">Jelajahi Item</router-link>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Search, Map as MapIcon } from 'lucide-vue-next'
import { api } from '../api'
import ItemCard from '../components/items/ItemCard.vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const searchQuery = ref('')
const categories = ref([])
const recent = ref([])
const loading = ref(true)

const stats = ref([
  { label: 'Item Dilaporkan', value: 1284 },
  { label: 'Item Ditemukan', value: 827 },
  { label: 'Item Dikembalikan', value: 642 }
])

const steps = [
  { title: 'Lapor', desc: 'Laporkan barang yang hilang atau ditemukan lengkap dengan foto dan lokasi.' },
  { title: 'Dapatkan kecocokan', desc: 'Sistem mencocokkan laporan dengan barang serupa — kemungkinan cocok muncul otomatis.' },
  { title: 'Klaim & kembali', desc: 'Klaim barangmu, lalui verifikasi kepemilikan, dan terima kembali barangmu.' }
]

function goSearch() {
  router.push({ path: '/explore', query: { q: searchQuery.value } })
}

onMounted(async () => {
  try {
    const [cats, items] = await Promise.all([
      api.get('/api/categories'),
      api.get('/api/items?per_page=8')
    ])
    categories.value = cats
    recent.value = items.items
  } catch {
    /* graceful fallback */
  } finally {
    loading.value = false
  }
})
</script>