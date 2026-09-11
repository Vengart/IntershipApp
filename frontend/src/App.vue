<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const auth = useAuthStore()

const showChrome = computed(() => route.name !== 'login')
</script>

<template>
  <div v-if="showChrome" class="min-h-screen flex">
    <aside class="w-56 shrink-0 bg-surface border-r border-border flex flex-col">
      <div class="px-4 py-5 border-b border-border">
        <h1 class="font-display text-lg text-ink leading-tight">Registru<br />Practică</h1>
      </div>

      <nav class="flex-1 px-2 py-4 space-y-1">
        <RouterLink
          :to="{ name: 'interns-list' }"
          class="block px-3 py-2 rounded text-sm text-ink-muted hover:bg-surface-raised hover:text-ink"
          active-class="bg-accent-soft text-ink"
        >
          Practicanți
        </RouterLink>
        <RouterLink
          :to="{ name: 'audit-logs' }"
          class="block px-3 py-2 rounded text-sm text-ink-muted hover:bg-surface-raised hover:text-ink"
          active-class="bg-accent-soft text-ink"
        >
          Jurnal modificări
        </RouterLink>
      </nav>

      <div class="px-4 py-4 border-t border-border text-sm">
        <p class="text-ink">{{ auth.user?.username }}</p>
        <p class="text-ink-muted text-xs mb-3">{{ auth.user?.role === 'operator' ? 'Operator' : 'Auditor' }}</p>
        <button
          @click="auth.logout(); $router.push({ name: 'login' })"
          class="text-ink-muted hover:text-danger text-xs"
        >
          Ieșire
        </button>
      </div>
    </aside>

    <main class="flex-1 p-6 overflow-auto">
      <RouterView />
    </main>
  </div>

  <RouterView v-else />
</template>
