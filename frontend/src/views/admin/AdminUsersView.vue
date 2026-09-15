<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-3xl font-bold mb-6">Manajemen Pengguna</h1>
    <AdminTabs />

    <div v-if="loading" class="space-y-3">
      <div v-for="i in 6" :key="i" class="card h-16 animate-pulse bg-slate-100" />
    </div>

    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-text-secondary text-xs uppercase tracking-wide">
            <tr>
              <th class="text-left px-5 py-3 font-semibold">Pengguna</th>
              <th class="text-left px-5 py-3 font-semibold">Email</th>
              <th class="text-left px-5 py-3 font-semibold">Peran</th>
              <th class="text-left px-5 py-3 font-semibold">Laporan</th>
              <th class="text-left px-5 py-3 font-semibold">Bergabung</th>
              <th class="px-5 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50 transition-colors">
              <td class="px-5 py-3.5 font-medium">{{ u.name }}</td>
              <td class="px-5 py-3.5 text-text-secondary">{{ u.email }}</td>
              <td class="px-5 py-3.5">
                <span
                  class="px-2 py-1 rounded-full text-xs font-semibold"
                  :class="roleClass(u.role)"
                >
                  {{ u.role }}
                </span>
              </td>
              <td class="px-5 py-3.5 text-text-secondary">{{ u.reports_count }}</td>
              <td class="px-5 py-3.5 text-text-muted">{{ u.created_at.slice(0, 10) }}</td>
              <td class="px-5 py-3.5">
                <select class="input w-auto py-1.5 text-xs" :value="u.role" @change="update(u, $event)">
                  <option value="student">student</option>
                  <option value="staff">staff</option>
                  <option value="admin">admin</option>
                </select>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api, toast } from '../../api'
import AdminTabs from '../../components/ui/AdminTabs.vue'

const users = ref([])
const loading = ref(true)

function roleClass(role) {
  if (role === 'admin') return 'bg-danger/10 text-danger'
  if (role === 'staff') return 'bg-primary/10 text-primary'
  return 'bg-slate-100 text-text-secondary'
}

async function update(u, e) {
  const role = e.target.value
  try {
    await api.put(`/api/admin/users/${u.id}/role`, { role })
    u.role = role
    toast(`Peran ${u.name} -> ${role}`)
  } catch (err) {
    toast(err.message, 'error')
  }
}

onMounted(async () => {
  try {
    users.value = await api.get('/api/admin/users')
  } finally {
    loading.value = false
  }
})
</script>