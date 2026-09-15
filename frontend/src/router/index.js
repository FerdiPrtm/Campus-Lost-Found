import { createRouter, createWebHistory } from 'vue-router'
import { authStore } from '../store/auth'

const routes = [
  { path: '/', name: 'home', component: () => import('../views/HomeView.vue') },
  { path: '/explore', name: 'explore', component: () => import('../views/items/ExploreView.vue') },
  { path: '/items/:id', name: 'item-detail', component: () => import('../views/items/ItemDetailView.vue') },
  { path: '/login', name: 'login', component: () => import('../views/auth/LoginView.vue') },
  { path: '/register', name: 'register', component: () => import('../views/auth/RegisterView.vue') },
  { path: '/report/lost', name: 'report-lost', meta: { requiresAuth: true }, component: () => import('../views/items/ReportItemView.vue'), props: { type: 'lost' } },
  { path: '/report/found', name: 'report-found', meta: { requiresAuth: true }, component: () => import('../views/items/ReportItemView.vue'), props: { type: 'found' } },
  { path: '/dashboard', name: 'dashboard', meta: { requiresAuth: true }, component: () => import('../views/dashboard/UserDashboardView.vue') },
  { path: '/notifications', name: 'notifications', meta: { requiresAuth: true }, component: () => import('../views/notification/NotificationsView.vue') },
  { path: '/admin', name: 'admin', meta: { requiresAdmin: true }, component: () => import('../views/admin/AdminDashboardView.vue') },
  { path: '/admin/reports', name: 'admin-reports', meta: { requiresAdmin: true }, component: () => import('../views/admin/AdminReportsView.vue') },
  { path: '/admin/claims', name: 'admin-claims', meta: { requiresAdmin: true }, component: () => import('../views/admin/AdminClaimsView.vue') },
  { path: '/admin/users', name: 'admin-users', meta: { requiresAdmin: true }, component: () => import('../views/admin/AdminUsersView.vue') },
  { path: '/:pathMatch(.*)*', redirect: '/' }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 })
})

router.beforeEach((to) => {
  if (to.meta.requiresAdmin && !authStore.isAdmin.value) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.meta.requiresAuth && !authStore.isAuthed.value) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if ((to.name === 'login' || to.name === 'register') && authStore.isAuthed.value) {
    return { name: 'home' }
  }
  return true
})

export default router