import { reactive } from 'vue'

export const api = {
  async request(method, url, options = {}) {
    const headers = { ...(options.headers || {}) }
    if (options.json !== false && options.body && !(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json'
    }
    if (method !== 'GET') {
      headers['X-CSRF-Token'] = localStorage.getItem('clf_csrf') || ''
    }
    const res = await fetch(url, {
      method,
      credentials: 'include',
      headers,
      body: options.body instanceof FormData ? options.body : options.body ? JSON.stringify(options.body) : undefined
    })
    const isJson = res.headers.get('content-type')?.includes('application/json')
    const data = isJson ? await res.json() : null
    if (!res.ok) {
      throw new Error(data?.message || 'Terjadi kesalahan. Silakan coba lagi.')
    }
    return data?.data ?? data
  },

  get(url) {
    return this.request('GET', url)
  },
  post(url, body, options = {}) {
    return this.request('POST', url, { ...options, body })
  },
  put(url, body) {
    return this.request('PUT', url, { body })
  },
  del(url) {
    return this.request('DELETE', url)
  }
}

export const toastState = reactive({
  visible: false,
  message: '',
  type: 'success'
})

let toastTimer = null
export function toast(message, type = 'success') {
  toastState.message = message
  toastState.type = type
  toastState.visible = true
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => (toastState.visible = false), 3500)
}