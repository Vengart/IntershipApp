<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const username = ref('')
const password = ref('')
const error = ref('')
const isSubmitting = ref(false)

async function handleSubmit() {
  error.value = ''
  isSubmitting.value = true

  try {
    await auth.login(username.value, password.value)
    router.push({ name: 'interns-list' })
  } catch (e) {
    error.value = e.message
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
      <div class="mb-8 text-center">
        <h1 class="font-display text-2xl text-ink">Registru Practică</h1>
        <p class="mt-1 text-sm text-ink-muted">Autentificare</p>
      </div>

      <form @submit.prevent="handleSubmit" class="bg-surface border border-border rounded-md p-6 space-y-4">
        <div>
          <label for="username" class="block text-sm text-ink-muted mb-1">Utilizator</label>
          <input
            id="username"
            v-model="username"
            type="text"
            required
            autofocus
            class="w-full bg-canvas border border-border rounded px-3 py-2 text-ink
                   focus:outline-none focus:ring-2 focus:ring-accent-strong"
          />
        </div>

        <div>
          <label for="password" class="block text-sm text-ink-muted mb-1">Parolă</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            class="w-full bg-canvas border border-border rounded px-3 py-2 text-ink
                   focus:outline-none focus:ring-2 focus:ring-accent-strong"
          />
        </div>

        <p v-if="error" class="text-sm text-danger">{{ error }}</p>

        <button
          type="submit"
          :disabled="isSubmitting"
          class="w-full bg-accent hover:bg-accent-strong text-white rounded px-3 py-2
                 transition-colors disabled:opacity-50"
        >
          {{ isSubmitting ? 'Se conectează…' : 'Autentificare' }}
        </button>
      </form>
    </div>
  </div>
</template>
