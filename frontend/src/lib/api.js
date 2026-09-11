import { API_BASE_URL } from '@/config'
import { useAuthStore } from '@/stores/auth'

/**
 * Обёртка над fetch: сама добавляет Authorization-заголовок и базовый URL,
 * сама парсит JSON и бросает понятную ошибку при неуспешном ответе.
 *
 * @param {string} path — например '/interns' или '/interns/5'
 * @param {RequestInit} options — обычные опции fetch (method, body и т.д.)
 */
export async function apiFetch(path, options = {}) {
  const auth = useAuthStore()

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {}),
      ...options.headers,
    },
  })

  // 401 — токен истёк или невалиден. Разлогиниваем и кидаем на /login,
  // чтобы пользователь не смотрел на "битую" страницу без понимания почему.
  if (response.status === 401) {
    auth.logout()
    window.location.href = '/login'
    throw new Error('Сессия истекла, войдите заново')
  }

  const body = await response.json()

  if (!response.ok) {
    throw new Error(body.error?.message || 'Ошибка запроса')
  }

  return body.data
}
