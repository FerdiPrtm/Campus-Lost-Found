import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { loadCsrf, authStore } from './store/auth'
import './style.css'

authStore.bootstrap()
loadCsrf()

createApp(App).use(router).mount('#app')