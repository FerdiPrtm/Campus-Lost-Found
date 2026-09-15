<template>
  <footer class="border-t border-slate-200 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 grid gap-8 md:grid-cols-3">
      <div>
        <div class="flex items-center gap-2 font-bold text-lg mb-3">
          <span class="w-8 h-8 rounded-xl bg-primary flex items-center justify-center text-white">
            <Search class="w-4 h-4" />
          </span>
          <span>Campus Lost<span class="text-primary"> &amp; Found</span></span>
        </div>
        <p class="text-sm text-text-secondary leading-relaxed">
          Barang hilang? Temukan kembali. Platform terpusat untuk melaporkan,
          mencocokkan, dan mengembalikan barang hilang di kampus.
        </p>
      </div>
      <div>
        <h3 class="text-sm font-semibold mb-3">Platform</h3>
        <ul class="space-y-2 text-sm text-text-secondary">
          <li><router-link to="/explore" class="hover:text-primary">Jelajahi Item</router-link></li>
          <li><router-link to="/report/lost" class="hover:text-primary">Lapor Barang Hilang</router-link></li>
          <li><router-link to="/report/found" class="hover:text-primary">Lapor Barang Ditemukan</router-link></li>
        </ul>
      </div>
      <div>
        <h3 class="text-sm font-semibold mb-3">Kampus</h3>
        <p class="text-sm text-text-secondary">
          Dibangun untuk komunitas kampus. Temukan kembali barangmu lebih cepat
          lewat kecocokan otomatis dan verifikasi klaim yang aman.
        </p>
      </div>
    </div>
    <div class="border-t border-slate-100 py-4 text-center text-xs text-text-muted">
      © {{ new Date().getFullYear() }} Campus Lost &amp; Found. Proyek semester.
    </div>
  </footer>
</template>

<script setup>
import { Search } from 'lucide-vue-next'
</script>