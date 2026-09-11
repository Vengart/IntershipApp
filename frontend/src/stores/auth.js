import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { API_BASE_URL } from '@/config'

export const useAuthStore = defineStore('auth', () => {
  // Читаем из localStorage при инициализации — чтобы обновление страницы (F5)
  // не разлогинивало пользователя.
  const token = ref(localStorage.getItem('token') || null)
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  const isAuthenticated = computed(() => token.value !== null)
  const isOperator = computed(() => user.value?.role === 'operator')

  async function login(username, password) {
    const response = await fetch(`${API_BASE_URL}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password }),
    })

    const body = await response.json()

    if (!response.ok) {
      // Бэк возвращает { error: { message, code } } — пробрасываем текст выше,
      // чтобы форма логина могла показать его пользователю
      throw new Error(body.error?.message || 'Login failed')
    }

    token.value = body.data.token
    user.value = body.data.user

    localStorage.setItem('token', token.value)
    localStorage.setItem('user', JSON.stringify(user.value))
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  return { token, user, isAuthenticated, isOperator, login, logout }
})
