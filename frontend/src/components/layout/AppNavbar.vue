<template>
  <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <nav class="flex items-center justify-between h-16">
        <div class="flex items-center gap-1">
          <button
            class="md:hidden p-2 rounded-lg hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary/40"
            aria-label="Buka menu navigasi"
            aria-expanded="showSidebar"
            @click="showSidebar = !showSidebar"
          >
            <Menu class="w-6 h-6" />
          </button>
          <router-link to="/" class="flex items-center gap-2 font-bold text-lg text-text-primary">
            <span class="w-8 h-8 rounded-xl bg-primary flex items-center justify-center text-white">
              <Search class="w-4 h-4" />
            </span>
            <span>Campus Lost<span class="text-primary"> &amp; Found</span></span>
          </router-link>
        </div>

        <div class="hidden md:flex items-center gap-1">
<router-link to="/explore" class="px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" active-class="text-primary bg-primary/10">
            Jelajah
          </router-link>
          <router-link to="/report/lost" class="px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors">
            Lapor Hilang
          </router-link>
          <router-link to="/report/found" class="px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors">
            Lapor Ditemukan
          </router-link>
          <template v-if="isAuthed">
            <router-link to="/dashboard" class="px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" active-class="text-primary bg-primary/10">
              Laporanku
            </router-link>
            <router-link to="/messages" class="relative px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" aria-label="Chat">
              <MessageSquare class="w-5 h-5" />
              <span v-if="chatUnread" class="absolute -top-0.5 -right-0.5 min-w-4 h-4 px-1 rounded-full bg-primary text-white text-[10px] font-bold inline-flex items-center justify-center">
                {{ chatUnread }}
              </span>
            </router-link>
            <router-link to="/notifications" class="relative px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" aria-label="Notifikasi">
              <Bell class="w-5 h-5" />
              <span v-if="unread" class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-danger" />
            </router-link>
            <template v-if="isAdmin">
              <router-link to="/admin" class="px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors" active-class="text-primary bg-primary/10">
                Admin
              </router-link>
            </template>
          </template>
        </div>

        <div class="flex items-center gap-3">
          <template v-if="isAuthed">
            <router-link to="/report/lost" class="hidden sm:inline-flex btn btn-primary">
              + Lapor Baru
            </router-link>
            <div class="relative" ref="menuRef">
              <button
                class="w-11 h-11 rounded-full bg-primary text-white flex items-center justify-center font-semibold text-base focus:outline-none focus:ring-2 focus:ring-primary/40"
                :aria-label="`Menu akun untuk ${user.name}`"
                @click="open = !open"
              >
                {{ user.name.charAt(0).toUpperCase() }}
              </button>
              <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                leave-active-class="transition duration-75 ease-in"
                leave-to-class="opacity-0 -translate-y-1"
              >
                <div v-if="open" class="absolute right-0 mt-2 w-52 bg-white rounded-xl border border-slate-200 shadow-lg py-1.5">
                  <div class="px-4 py-2 border-b border-slate-100">
                    <p class="text-sm font-semibold truncate">{{ user.name }}</p>
                    <p class="text-xs text-text-muted truncate">{{ user.email }}</p>
                  </div>
<router-link to="/dashboard" class="block px-4 py-2 text-sm hover:bg-slate-50" @click="open = false">Laporanku</router-link>
                  <router-link to="/messages" class="block px-4 py-2 text-sm hover:bg-slate-50" @click="open = false">Chat</router-link>
                  <router-link to="/profile" class="block px-4 py-2 text-sm hover:bg-slate-50" @click="open = false">Profil</router-link>
                  <router-link to="/notifications" class="block px-4 py-2 text-sm hover:bg-slate-50" @click="open = false">Notifikasi</router-link>
                  <template v-if="isAdmin">
                    <router-link to="/admin" class="block px-4 py-2 text-sm hover:bg-slate-50" @click="open = false">Dasbor Admin</router-link>
                  </template>
                  <button class="w-full text-left px-4 py-2 text-sm text-danger hover:bg-red-50" @click="logout">Keluar</button>
                </div>
              </Transition>
            </div>
          </template>
          <template v-else>
            <router-link to="/login" class="btn btn-secondary hidden sm:inline-flex">Masuk</router-link>
            <router-link to="/register" class="btn btn-primary">Daftar</router-link>
          </template>
        </div>
      </nav>
    </div>

<div v-if="open" class="fixed inset-0 z-[-1]" @click="open = false" />
    <AppSidebar :open="showSidebar" @close="showSidebar = false" />
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { Search, Bell, MessageSquare, Menu, X } from 'lucide-vue-next'
import AppSidebar from './AppSidebar.vue'
import { authStore } from '../../store/auth'
import { api } from '../../api'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()
const open = ref(false)
const showSidebar = ref(false)
const unread = ref(0)
const chatUnread = ref(0)
const isAuthed = authStore.isAuthed
const isAdmin = authStore.isAdmin
const user = authStore.user

function logout() {
  authStore.logout()
  router.push('/')
}

let pollTimer = null
async function pollUnread() {
  if (!isAuthed.value) return
  try {
    const data = await api.get('/api/notifications')
    unread.value = data.unread
  } catch {
    /* ignore */
  }
  try {
    const data = await api.get('/api/messages/unread')
    chatUnread.value = data.unread
  } catch {
    /* ignore */
  }
}

onMounted(() => {
  pollUnread()
  pollTimer = setInterval(pollUnread, 20000)
})
watch(isAuthed, () => {
  unread.value = 0
  chatUnread.value = 0
})
watch(() => route.fullPath, pollUnread)
onUnmounted(() => clearInterval(pollTimer))
</script>
