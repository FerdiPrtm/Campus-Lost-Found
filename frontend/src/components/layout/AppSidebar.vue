<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div v-if="open" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm" @click="close" />
    </Transition>
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="-translate-x-full"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="-translate-x-full"
    >
      <aside v-if="open" class="fixed inset-y-0 left-0 z-50 w-64 max-w-[80vw] bg-white shadow-xl flex flex-col" role="dialog" aria-modal="true" aria-label="Menu navigasi">
        <div class="flex items-center justify-between px-4 h-16 border-b border-slate-200">
          <router-link to="/" class="flex items-center gap-2 font-bold text-lg text-text-primary" @click="close">
            <span class="w-8 h-8 rounded-xl bg-primary flex items-center justify-center text-white">
              <Search class="w-4 h-4" />
            </span>
            Campus <span class="text-primary">Lost &amp; Found</span>
          </router-link>
          <button class="p-2 rounded-lg hover:bg-slate-100" aria-label="Tutup menu" @click="close">
            <X class="w-5 h-5" />
          </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-1">
          <p class="px-3 pt-1 pb-2 text-xs font-semibold uppercase tracking-wide text-text-muted">Jelajah</p>
          <router-link to="/explore" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" active-class="text-primary bg-primary/10" @click="close">
            <Compass class="w-5 h-5" />
            Jelajah Barang
          </router-link>
          <router-link to="/report/lost" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" active-class="text-primary bg-primary/10" @click="close">
            <PackageSearch class="w-5 h-5" />
            Lapor Hilang
          </router-link>
          <router-link to="/report/found" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" active-class="text-primary bg-primary/10" @click="close">
            <PackageCheck class="w-5 h-5" />
            Lapor Ditemukan
          </router-link>

          <template v-if="isAuthed">
            <p class="px-3 pt-4 pb-2 text-xs font-semibold uppercase tracking-wide text-text-muted">Akunku</p>
            <router-link to="/dashboard" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" active-class="text-primary bg-primary/10" @click="close">
              <LayoutDashboard class="w-5 h-5" />
              Laporanku
            </router-link>
            <router-link to="/messages" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" active-class="text-primary bg-primary/10" @click="close">
              <MessageSquare class="w-5 h-5" />
              Chat
            </router-link>
            <router-link to="/notifications" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" active-class="text-primary bg-primary/10" @click="close">
              <Bell class="w-5 h-5" />
              Notifikasi
            </router-link>
            <router-link to="/profile" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" active-class="text-primary bg-primary/10" @click="close">
              <User class="w-5 h-5" />
              Profil
            </router-link>
            <router-link v-if="isAdmin" to="/admin" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" active-class="text-primary bg-primary/10" @click="close">
              <ShieldCheck class="w-5 h-5" />
              Dasbor Admin
            </router-link>
          </template>
        </nav>

        <div class="border-t border-slate-200 p-3">
          <template v-if="isAuthed">
            <div class="flex items-center gap-3 px-2 pb-3">
              <span class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-semibold">
                {{ user.name.charAt(0).toUpperCase() }}
              </span>
              <div class="min-w-0">
                <p class="text-sm font-semibold truncate">{{ user.name }}</p>
                <p class="text-xs text-text-muted truncate">{{ user.email }}</p>
              </div>
            </div>
            <button class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg text-danger hover:bg-red-50 transition-colors" @click="logout">
              <LogOut class="w-4 h-4" />
              Keluar
            </button>
          </template>
          <template v-else>
            <router-link to="/login" class="btn btn-secondary w-full mb-2" @click="close">Masuk</router-link>
            <router-link to="/register" class="btn btn-primary w-full" @click="close">Daftar</router-link>
          </template>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup>
import { Compass, PackageSearch, PackageCheck, LayoutDashboard, MessageSquare, Bell, User, ShieldCheck, LogOut, Search, X } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { authStore } from '../../store/auth'

const props = defineProps({
  open: { type: Boolean, default: false }
})
const emit = defineEmits(['close'])
const router = useRouter()
const isAuthed = authStore.isAuthed
const isAdmin = authStore.isAdmin
const user = authStore.user

function close() {
  emit('close')
}

function logout() {
  authStore.logout()
  emit('close')
  router.push('/')
}
</script>
