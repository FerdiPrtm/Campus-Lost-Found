<template>
  <div class="text-center py-16 text-text-muted">
    <Search class="w-12 h-12 mx-auto mb-4 opacity-40" />
    <h3 class="text-lg font-semibold text-text-primary mb-1">{{ title }}</h3>
    <p class="text-sm mb-6">{{ message }}</p>
    <button v-if="actionText" class="btn btn-secondary" @click="$emit('action')">
      {{ actionText }}
    </button>
  </div>
</template>

<script setup>
import { Search } from 'lucide-vue-next'
defineProps({
  title: { type: String, default: 'Tidak ada item' },
  message: { type: String, default: 'Coba kata kunci lain atau ubah filter kamu.' },
  actionText: { type: String, default: 'Bersihkan Filter' }
})
defineEmits(['action'])
</script>