<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">Pesan</h1>
      <span v-if="totalUnread" class="text-xs text-danger font-semibold">{{ totalUnread }} belum dibaca</span>
    </div>

    <p v-if="!isAuthed" class="card p-8 text-center text-text-secondary">
      Masuk untuk membuka obrolan.
      <router-link :to="{ name: 'login', query: { redirect: $route.fullPath } }" class="btn btn-primary mt-4">Masuk</router-link>
    </p>

    <div v-else class="grid lg:grid-cols-3 gap-5">
      <!-- Conversation list -->
      <aside :class="threadOpen ? 'hidden lg:block' : 'block'" class="lg:col-span-1">
        <div class="card overflow-hidden">
          <input v-model="search" type="text" class="border-b w-full px-4 py-3 text-sm focus:outline-none" placeholder="Cari percakapan..." />
          <div v-if="listLoading" class="p-4 space-y-3">
            <div v-for="i in 3" :key="i" class="h-14 rounded-lg bg-slate-100 animate-pulse" />
          </div>
          <div v-else-if="!filtered.length" class="p-6 text-center text-sm text-text-muted">
            Belum ada obrolan. Buka laporan yang disetujui lalu tekan "Chat".
          </div>
          <button
            v-for="c in filtered"
            :key="c.id"
            class="w-full flex items-start gap-3 p-3 text-left hover:bg-slate-50 transition-colors"
            :class="{ 'bg-primary/10': activeKey === c.id }"
            @click="openConversation(c)"
          >
            <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center font-semibold text-sm shrink-0">
              {{ (c.user.name || '?').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-2">
                <p class="font-semibold text-sm truncate">{{ c.user.name }}</p>
                <span class="text-[11px] text-text-muted shrink-0">{{ timeAgo(c.last_at) }}</span>
              </div>
              <p class="text-xs text-text-muted truncate">{{ c.item_type === 'found' ? 'Ditemukan' : 'Hilang' }} · {{ c.item_name }}</p>
              <p class="text-xs text-text-secondary truncate">{{ c.last_message }}</p>
            </div>
            <span v-if="c.unread" class="min-w-5 h-5 px-1.5 rounded-full bg-danger text-white text-[11px] font-bold inline-flex items-center justify-center">
              {{ c.unread }}
            </span>
          </button>
        </div>
      </aside>

      <!-- Thread -->
      <section :class="threadOpen ? 'block' : 'hidden lg:block'" class="lg:col-span-2">
        <div v-if="!threadOpen" class="card h-[26rem] flex items-center justify-center text-text-muted">
          <div class="text-center">
            <MessageSquare class="w-10 h-10 mx-auto mb-3 opacity-40" />
            <p class="text-sm">Pilih percakapan untuk mulai chat.</p>
          </div>
        </div>

        <div v-else class="card flex flex-col h-[32rem]">
          <div class="flex items-center gap-3 p-4 border-b border-slate-100">
            <button class="lg:hidden btn btn-secondary btn-sm" @click="closeThread">Kembali</button>
            <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center font-semibold text-sm">
              {{ (otherName || '?').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0">
              <p class="font-semibold text-sm">{{ otherName }}</p>
              <p class="text-xs text-text-muted truncate">Soal: {{ activeItemName }}</p>
            </div>
            <router-link :to="`/items/${activeItemId}`" class="ml-auto text-xs text-primary hover:underline shrink-0">Lihat laporan</router-link>
          </div>

          <div ref="threadBox" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50">
            <div v-if="threadLoading" class="text-center text-xs text-text-muted py-6">Memuat...</div>
            <div
              v-for="m in messages"
              :key="m.id"
              class="flex"
              :class="m.sender_id === meId ? 'justify-end' : 'justify-start'"
            >
              <div
                class="max-w-[75%] rounded-2xl px-3.5 py-2 text-sm whitespace-pre-wrap"
                :class="m.sender_id === meId ? 'bg-primary text-white rounded-br-sm' : 'bg-white border border-slate-200 rounded-bl-sm'"
              >
                <p>{{ m.body }}</p>
                <p class="text-[10px] mt-1 opacity-60 text-right">{{ time(m.created_at) }}</p>
              </div>
            </div>
          </div>

          <form class="p-3 border-t border-slate-100 flex items-end gap-2" @submit.prevent="send">
            <textarea
              v-model="draft"
              rows="1"
              class="input flex-1 resize-none max-h-32"
              placeholder="Tulis pesan..."
              @keydown.enter.exact.prevent="send"
            ></textarea>
            <button class="btn btn-primary" :disabled="sending || !draft.trim()">
              <Send class="w-4 h-4" />
            </button>
          </form>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { MessageSquare, Send } from 'lucide-vue-next'
import { api } from '../../api'
import { authStore } from '../../store/auth'

const route = useRoute()
const router = useRouter()
const isAuthed = authStore.isAuthed
const meId = authStore.user.value?.id

const conversations = ref([])
const listLoading = ref(true)
const totalUnread = ref(0)
const search = ref('')

const threadOpen = ref(false)
const activeKey = ref('')
const activeItemId = ref(null)
const activeItemName = ref('')
const otherId = ref(null)
const otherName = ref('')
const messages = ref([])
const threadLoading = ref(false)
const draft = ref('')
const sending = ref(false)
const threadBox = ref(null)

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return conversations.value
  return conversations.value.filter((c) => c.user.name.toLowerCase().includes(q) || c.item_name.toLowerCase().includes(q))
})

let listTimer = null
let threadTimer = null

function timeAgo(str) {
  const s = Math.floor((Date.now() - new Date(str).getTime()) / 1000)
  if (s < 60) return 'baru saja'
  if (s < 3600) return `${Math.floor(s / 60)}m`
  if (s < 86400) return `${Math.floor(s / 3600)}j`
  return new Date(str).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}

function time(str) {
  return new Date(str).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

async function loadConversations() {
  try {
    const data = await api.get('/api/messages/conversations')
    conversations.value = data.conversations
    totalUnread.value = data.unread
  } catch {
    /* ignore */
  } finally {
    listLoading.value = false
  }
}

async function openConversation(c) {
  threadOpen.value = true
  activeKey.value = c.id
  activeItemId.value = c.item_id
  activeItemName.value = c.item_name
  otherId.value = c.user.id
  otherName.value = c.user.name
  router.replace({ query: { ...route.query, item: c.item_id, with: c.user.id } })
  await loadThread()
}

async function loadThread() {
  if (!activeItemId.value || !otherId.value) return
  threadLoading.value = true
  try {
    const data = await api.get(`/api/messages/thread?item_id=${activeItemId.value}&with=${otherId.value}`)
    if (data.other?.name) otherName.value = data.other.name
    messages.value = data.messages
    loadConversations()
    scrollBottom()
  } finally {
    threadLoading.value = false
  }
}

async function send() {
  const body = draft.value.trim()
  if (!body) return
  sending.value = true
  try {
    const m = await api.post('/api/messages', { item_id: activeItemId.value, receiver_id: otherId.value, body })
    messages.value.push(m)
    draft.value = ''
    scrollBottom()
    loadConversations()
  } catch (e) {
    alert(e.message)
  } finally {
    sending.value = false
  }
}

function scrollBottom() {
  nextTick(() => {
    if (threadBox.value) threadBox.value.scrollTop = threadBox.value.scrollHeight
  })
}

function closeThread() {
  threadOpen.value = false
  activeKey.value = ''
  router.replace({ query: {} })
}

watch(threadOpen, (open) => {
  clearInterval(threadTimer)
  if (open) {
    threadTimer = setInterval(loadThread, 5000)
  }
})

async function openFromQuery() {
  const q = route.query
  if (!q.item || !q.with) return
  if (activeItemId.value === Number(q.item) && otherId.value === Number(q.with)) return
  activeItemId.value = Number(q.item)
  otherId.value = Number(q.with)
  threadOpen.value = true
  const itemId = activeItemId.value
  const target = conversations.value.find((c) => c.item_id === itemId && c.user.id === Number(q.with))
  if (target) {
    activeKey.value = target.id
    activeItemName.value = target.item_name
    otherName.value = target.user.name
  } else {
    activeItemName.value = `Item #${itemId}`
    otherName.value = 'Pengguna'
  }
  try {
    const data = await api.get(`/api/messages/thread?item_id=${itemId}&with=${q.with}`)
    if (data.other?.name) otherName.value = data.other.name
    if (data.item_name) activeItemName.value = data.item_name
    messages.value = data.messages
    const fresh = await api.get('/api/messages/conversations')
    conversations.value = fresh.conversations
    totalUnread.value = fresh.unread
    const t = conversations.value.find((c) => c.item_id === itemId && c.user.id === Number(q.with))
    if (t) {
      activeKey.value = t.id
      activeItemName.value = t.item_name
    }
    scrollBottom()
  } catch {
    threadOpen.value = false
  }
}

watch(
  () => route.query,
  openFromQuery
)

onMounted(() => {
  loadConversations()
  listTimer = setInterval(loadConversations, 10000)
  openFromQuery()
})

onUnmounted(() => {
  clearInterval(listTimer)
  clearInterval(threadTimer)
})
</script>

<style scoped>
.btn-sm {
  padding: 0.35rem 0.6rem;
  font-size: 0.75rem;
}
</style>