<template>
  <router-link
    :to="`/items/${item.id}`"
    class="card card-hover overflow-hidden group flex flex-col"
  >
    <div class="relative h-44 bg-slate-100 overflow-hidden">
      <img
        v-if="item.image"
        :src="`/storage/${item.image}`"
        :alt="item.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        loading="lazy"
      />
      <div v-else class="w-full h-full flex items-center justify-center text-text-muted">
        <Image class="w-10 h-10" />
      </div>
      <div class="absolute top-3 left-3">
        <StatusBadge :status="item.status" />
      </div>
    </div>
    <div class="p-4 flex flex-col flex-1">
      <p class="text-xs text-text-muted font-medium">{{ item.category }}</p>
      <h3 class="font-semibold text-text-primary mt-0.5 line-clamp-1">{{ item.name }}</h3>
      <div class="mt-3 space-y-1 text-sm text-text-secondary flex-1">
        <p class="flex items-center gap-1.5">
          <MapPin class="w-3.5 h-3.5 text-text-muted" /> {{ item.location }}
        </p>
        <p class="flex items-center gap-1.5">
          <Clock class="w-3.5 h-3.5 text-text-muted" /> {{ timeAgo(item.created_at) }}
        </p>
      </div>
      <div class="mt-4">
        <span class="text-sm font-semibold text-primary group-hover:underline">
          Lihat Detail →
        </span>
      </div>
    </div>
  </router-link>
</template>

<script setup>
import { Image, MapPin, Clock } from 'lucide-vue-next'
import StatusBadge from '../ui/StatusBadge.vue'

defineProps({ item: { type: Object, required: true } })

function timeAgo(str) {
  const diff = (Date.now() - new Date(str).getTime()) / 1000
  if (diff < 60) return 'Baru saja'
  if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`
  if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`
  if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`
  return new Date(str).toLocaleDateString('id-ID')
}
</script>