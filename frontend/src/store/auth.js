import { reactive, computed } from 'vue'

const state = reactive({
  user: JSON.parse(localStorage.getItem('clf_user') || 'null'),
  csrf: null
})

export const authStore = {
  state,
  user: computed(() => state.user),
  isAuthed: computed(() => !!state.user),
  isAdmin: computed(() => ['admin', 'staff'].includes(state.user?.role)),

  setUser(user) {
    state.user = user
    if (user) localStorage.setItem('clf_user', JSON.stringify(user))
    else localStorage.removeItem('clf_user')
  },

  setCsrf(token) {
    state.csrf = token
  },

  async bootstrap() {
    try {
      const res = await fetch('/api/auth/me', { credentials: 'include' })
      if (res.ok) {
        const { data } = await res.json()
        this.setUser(data)
      }
    } catch {
      /* not authenticated */
    }
  },

  async logout() {
    await fetch('/api/auth/logout', {
      method: 'POST',
      credentials: 'include',
      headers: { 'X-CSRF-Token': state.csrf || '' }
    })
    this.setUser(null)
  }
}

export async function loadCsrf() {
  try {
    const res = await fetch('/api/auth/csrf', { credentials: 'include' })
    if (res.ok) {
      const { data } = await res.json()
      authStore.setCsrf(data.csrf_token)
      localStorage.setItem('clf_csrf', data.csrf_token)
    }
  } catch {
    /* offline */
  }
}