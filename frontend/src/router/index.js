import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { public: true },
  },
  {
    path: '/',
    name: 'interns-list',
    component: () => import('@/views/InternsListView.vue'),
  },
  {
    path: '/interns/new',
    name: 'intern-create',
    component: () => import('@/views/InternFormView.vue'),
    meta: { operatorOnly: true },
  },
  {
    path: '/interns/:id/edit',
    name: 'intern-edit',
    component: () => import('@/views/InternFormView.vue'),
    props: true,
    meta: { operatorOnly: true },
  },
  {
    path: '/audit-logs',
    name: 'audit-logs',
    component: () => import('@/views/AuditLogView.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// Глобальный guard: пускаем на /login без токена, всё остальное требует входа.
// operatorOnly-страницы дополнительно закрыты для auditor — это чисто UX-защита
// (спрятать кнопки/страницы), реальная проверка прав всё равно на бэке.
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (!to.meta.public && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.operatorOnly && !auth.isOperator) {
    return { name: 'interns-list' }
  }

  if (to.name === 'login' && auth.isAuthenticated) {
    return { name: 'interns-list' }
  }
})

export default router
