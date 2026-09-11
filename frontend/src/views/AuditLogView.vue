<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { apiFetch } from '@/lib/api'

const logs = ref([])
const interns = ref([])
const selectedInternId = ref('')
const isLoading = ref(true)
const errorMessage = ref('')

async function loadLogs() {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const path = selectedInternId.value
      ? `/audit-logs?intern_id=${selectedInternId.value}`
      : '/audit-logs'
    logs.value = await apiFetch(path)
  } catch (e) {
    errorMessage.value = e.message
  } finally {
    isLoading.value = false
  }
}

// Поля, которые меняются в UPDATE — сравниваем old_values/new_values
// и показываем только то, что реально изменилось, а не все 16 колонок
function changedFields(log) {
  if (log.action !== 'UPDATE') return []
  const changes = []
  for (const key of Object.keys(log.new_values)) {
    if (JSON.stringify(log.old_values[key]) !== JSON.stringify(log.new_values[key])) {
      changes.push({ key, from: log.old_values[key], to: log.new_values[key] })
    }
  }
  return changes
}

function formatDate(isoString) {
  return new Date(isoString).toLocaleString('ro-RO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(async () => {
  try {
    interns.value = await apiFetch('/interns')
  } catch (e) {
    errorMessage.value = e.message
  }
  await loadLogs()
})

watch(selectedInternId, loadLogs)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="font-display text-2xl text-ink">Jurnal modificări</h1>
      <select
        v-model="selectedInternId"
        class="bg-surface border border-border rounded px-3 py-1.5 text-sm text-ink
               focus:outline-none focus:ring-2 focus:ring-accent-strong"
      >
        <option value="">Toți practicanți</option>
        <option v-for="i in interns" :key="i.id" :value="String(i.id)">{{ i.full_name }}</option>
      </select>
    </div>

    <p v-if="errorMessage" class="text-danger text-sm mb-4">{{ errorMessage }}</p>
    <p v-else-if="isLoading" class="text-ink-muted text-sm">Se încarcă…</p>

    <div v-else class="space-y-2">
      <div
        v-for="log in logs"
        :key="log.id"
        class="bg-surface border border-border rounded-md px-4 py-3 text-sm"
      >
        <div class="flex items-center justify-between mb-1">
          <span class="text-ink">
            <span class="font-mono text-ink-muted mr-2">#{{ log.intern_id }}</span>
            {{ log.intern_name }}
          </span>
          <span class="text-ink-muted text-xs font-mono">{{ formatDate(log.created_at) }}</span>
        </div>

        <div class="text-ink-muted text-xs mb-2">
          <span :class="log.action === 'INSERT' ? 'text-good' : 'text-warn'">
            {{ log.action === 'INSERT' ? 'Creat' : 'Modificat' }}
          </span>
          de {{ log.changed_by }}
        </div>

        <ul v-if="log.action === 'UPDATE'" class="space-y-0.5">
          <li v-for="change in changedFields(log)" :key="change.key" class="text-xs">
            <span class="text-ink-muted">{{ change.key }}:</span>
            <span class="text-danger line-through mr-1">{{ change.from || '—' }}</span>
            <span class="text-good">{{ change.to || '—' }}</span>
          </li>
        </ul>
      </div>

      <p v-if="logs.length === 0" class="text-ink-muted text-sm text-center py-6">
        Nicio modificare înregistrată.
      </p>
    </div>
  </div>
</template>
