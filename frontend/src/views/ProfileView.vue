<template>
  <div class="max-w-xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-3xl font-bold mb-8">Profil</h1>
    <div class="space-y-6">
      <form class="card p-6 space-y-4" @submit.prevent="saveProfile">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-semibold text-lg shrink-0">
            {{ (name || '?').charAt(0).toUpperCase() }}
          </div>
          <h2 class="font-semibold text-lg">Informasi Akun</h2>
        </div>
        <div>
          <label class="label" for="p-name">Nama lengkap</label>
          <input v-model="name" id="p-name" type="text" class="input" required autocomplete="name" />
        </div>
        <div>
          <label class="label" for="p-email">Email</label>
          <input :value="authStore.user.value?.email" id="p-email" type="email" class="input" disabled />
          <p class="text-xs text-text-muted mt-1">Email tidak dapat diubah.</p>
        </div>
        <p v-if="profileError" class="text-sm text-danger">{{ profileError }}</p>
        <button class="btn btn-primary w-full" :disabled="savingProfile">
          {{ savingProfile ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </form>

      <form class="card p-6 space-y-4" @submit.prevent="savePassword">
        <h2 class="font-semibold text-lg">Ganti Password</h2>
        <div>
          <label class="label" for="pw-current">Password saat ini</label>
          <input v-model="passwordForm.current_password" id="pw-current" type="password" class="input" required autocomplete="current-password" />
        </div>
        <div>
          <label class="label" for="pw-new">Password baru (min. 8 karakter)</label>
          <input v-model="passwordForm.password" id="pw-new" type="password" class="input" required minlength="8" autocomplete="new-password" />
        </div>
        <div>
          <label class="label" for="pw-confirm">Ulangi password baru</label>
          <input v-model="passwordForm.password_confirmation" id="pw-confirm" type="password" class="input" required minlength="8" autocomplete="new-password" />
        </div>
        <p v-if="passwordError" class="text-sm text-danger">{{ passwordError }}</p>
        <button class="btn btn-primary w-full" :disabled="savingPassword">
          {{ savingPassword ? 'Menyimpan...' : 'Ganti Password' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { api, toast } from '../api'
import { authStore } from '../store/auth'

const name = ref(authStore.user.value?.name || '')
const savingProfile = ref(false)
const profileError = ref('')
const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' })
const savingPassword = ref(false)
const passwordError = ref('')

async function saveProfile() {
  savingProfile.value = true
  profileError.value = ''
  try {
    const updated = await api.put('/api/auth/profile', { name: name.value })
    authStore.setUser(updated)
    toast('Nama diperbarui.')
  } catch (e) {
    profileError.value = e.message
  } finally {
    savingProfile.value = false
  }
}

async function savePassword() {
  savingPassword.value = true
  passwordError.value = ''
  try {
    await api.put('/api/auth/password', passwordForm.value)
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
    toast('Password diganti.')
  } catch (e) {
    passwordError.value = e.message
  } finally {
    savingPassword.value = false
  }
}
</script>