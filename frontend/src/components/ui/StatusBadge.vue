<template>
  <span
    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
    :class="config"
  >
    <span class="w-1.5 h-1.5 rounded-full" :class="dotClass" />
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ status: { type: String, default: 'lost' } })

const map = {
  lost: { label: 'HILANG', classes: 'bg-red-50 text-danger', dot: 'bg-danger' },
  found: { label: 'DITEMUKAN', classes: 'bg-amber-50 text-warning', dot: 'bg-warning' },
  claimed: { label: 'DIKLAIM', classes: 'bg-blue-50 text-blue-600', dot: 'bg-blue-500' },
  verified: { label: 'TERVERIFIKASI', classes: 'bg-purple-50 text-purple-600', dot: 'bg-purple-500' },
  returned: { label: 'DIKEMBALIKAN', classes: 'bg-emerald-50 text-success', dot: 'bg-success' },
  pending: { label: 'MENUNGGU', classes: 'bg-slate-100 text-text-secondary', dot: 'bg-text-muted' },
  approved: { label: 'DISETUJUI', classes: 'bg-emerald-50 text-success', dot: 'bg-success' },
  rejected: { label: 'DITOLAK', classes: 'bg-danger/10 text-danger', dot: 'bg-danger' }
}

const config = computed(() => map[props.status]?.classes || map.lost.classes)
const dotClass = computed(() => map[props.status]?.dot || map.lost.dot)
const label = computed(() => map[props.status]?.label || props.status)
</script>