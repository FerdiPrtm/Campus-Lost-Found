<template>
  <div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="w-12 h-12 rounded-2xl bg-primary mx-auto mb-4 flex items-center justify-center text-white">
          <Search class="w-6 h-6" />
        </div>
        <h1 class="text-2xl font-bold">Buat akunmu</h1>
        <p class="text-sm text-text-secondary mt-1">Mulai lapor dan temukan kembali barangmu.</p>
      </div>

      <form class="card p-6 space-y-4" @submit.prevent="submit">
        <div>
          <label class="label" for="name">Nama lengkap</label>
          <input v-model="form.name" id="name" type="text" class="input" required autocomplete="name" />
        </div>
        <div>
          <label class="label" for="email">Email</label>
          <input v-model="form.email" id="email" type="email" class="input" required autocomplete="email" />
        </div>
        <div>
          <label class="label" for="password">Password</label>
          <input v-model="form.password" id="password" type="password" class="input" required minlength="8" autocomplete="new-password" />
          <p class="text-xs text-text-muted mt-1">Min. 8 karakter</p>
        </div>
        <p v-if="error" class="text-sm text-danger">{{ error }}</p>
        <button class="btn btn-primary w-full py-3" :disabled="loading">
          {{ loading ? 'Membuat...' : 'Buat Akun' }}
        </button>
      </form>

      <p class="text-center text-sm text-text-secondary mt-6">
        Sudah punya akun?
        <router-link to="/login" class="text-primary font-semibold hover:underline">Masuk</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Search } from 'lucide-vue-next'
import { api, toast } from '../../api'
import { authStore, loadCsrf } from '../../store/auth'
import { useRouter } from 'vue-router'

const router = useRouter()
const loading = ref(false)
const error = ref('')
const form = reactive({ name: '', email: '', password: '' })

async function submit() {
  loading.value = true
  error.value = ''
  try {
    const user = await api.post('/api/auth/register', form)
    authStore.setUser(user)
    await loadCsrf()
    toast('Akun berhasil dibuat!')
    router.push('/dashboard')
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>