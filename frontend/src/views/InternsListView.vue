<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { apiFetch } from '@/lib/api'
import { API_BASE_URL } from '@/config'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const interns = ref([])
const directions = ref([])
const isLoading = ref(true)
const errorMessage = ref('')

// Фильтры — держим отдельно от загруженных данных, применяются на клиенте,
// т.к. GET /interns у нас и так отдаёт всё сразу (см. договорённость про is_active)
const filters = ref({
  university: '',
  specialty: '',
  directionId: '',
  showInactive: false,
})

const filteredInterns = computed(() => {
  return interns.value.filter((intern) => {
    if (!filters.value.showInactive && !intern.is_active) return false
    if (filters.value.directionId && String(intern.direction_id) !== filters.value.directionId) return false
    if (
      filters.value.university &&
      !intern.university.toLowerCase().includes(filters.value.university.toLowerCase())
    )
      return false
    if (
      filters.value.specialty &&
      !intern.specialty.toLowerCase().includes(filters.value.specialty.toLowerCase())
    )
      return false
    return true
  })
})

async function loadData() {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const [internsData, directionsData] = await Promise.all([
      apiFetch('/interns'),
      apiFetch('/directions'),
    ])
    interns.value = internsData
    directions.value = directionsData
  } catch (e) {
    errorMessage.value = e.message
  } finally {
    isLoading.value = false
  }
}

async function handleExport() {
  const response = await fetch(`${API_BASE_URL}/export/excel`, {
    headers: { Authorization: `Bearer ${auth.token}` },
  })
  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `interns_export_${new Date().toISOString().slice(0, 10)}.xlsx`
  link.click()
  URL.revokeObjectURL(url)
}

onMounted(loadData)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="font-display text-2xl text-ink">Practicanți</h1>
      <div class="flex gap-2">
        <button
          @click="handleExport"
          class="px-3 py-2 text-sm border border-border rounded text-ink-muted hover:text-ink hover:border-accent-strong"
        >
          Export Excel
        </button>
        <RouterLink
          v-if="auth.isOperator"
          :to="{ name: 'intern-create' }"
          class="px-3 py-2 text-sm bg-accent hover:bg-accent-strong text-white rounded"
        >
          + Practicant nou
        </RouterLink>
      </div>
    </div>

    <!-- Filtre -->
    <div class="flex flex-wrap gap-3 mb-4">
      <input
        v-model="filters.university"
        type="text"
        placeholder="Universitate"
        class="bg-surface border border-border rounded px-3 py-1.5 text-sm text-ink placeholder:text-ink-muted
               focus:outline-none focus:ring-2 focus:ring-accent-strong"
      />
      <input
        v-model="filters.specialty"
        type="text"
        placeholder="Specialitate"
        class="bg-surface border border-border rounded px-3 py-1.5 text-sm text-ink placeholder:text-ink-muted
               focus:outline-none focus:ring-2 focus:ring-accent-strong"
      />
      <select
        v-model="filters.directionId"
        class="bg-surface border border-border rounded px-3 py-1.5 text-sm text-ink
               focus:outline-none focus:ring-2 focus:ring-accent-strong"
      >
        <option value="">Toate direcțiile</option>
        <option v-for="d in directions" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
      </select>
      <label class="flex items-center gap-2 text-sm text-ink-muted">
        <input v-model="filters.showInactive" type="checkbox" class="accent-accent" />
        Arată inactivi
      </label>
    </div>

    <p v-if="errorMessage" class="text-danger text-sm mb-4">{{ errorMessage }}</p>
    <p v-else-if="isLoading" class="text-ink-muted text-sm">Se încarcă…</p>

    <div v-else class="bg-surface border border-border rounded-md overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-border text-left text-ink-muted">
            <th class="px-3 py-2 font-normal font-mono">Nr</th>
            <th class="px-3 py-2 font-normal">Nume complet</th>
            <th class="px-3 py-2 font-normal">Universitate</th>
            <th class="px-3 py-2 font-normal">Specialitate</th>
            <th class="px-3 py-2 font-normal">Status</th>
            <th class="px-3 py-2 font-normal">Mentor</th>
            <th class="px-3 py-2 font-normal"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="intern in filteredInterns"
            :key="intern.id"
            class="border-b border-border last:border-0 hover:bg-surface-raised"
            :class="{ 'opacity-50': !intern.is_active }"
          >
            <td class="px-3 py-2 font-mono text-ink-muted">{{ intern.id }}</td>
            <td class="px-3 py-2 text-ink">
              <div class="max-w-[180px] truncate" :title="intern.full_name">{{ intern.full_name }}</div>
            </td>
            <td class="px-3 py-2 text-ink-muted">
              <div class="max-w-[160px] truncate" :title="intern.university">{{ intern.university }}</div>
            </td>
            <td class="px-3 py-2 text-ink-muted">
              <div class="max-w-[160px] truncate" :title="intern.specialty">{{ intern.specialty }}</div>
            </td>
            <td class="px-3 py-2 text-ink-muted">{{ intern.internship_status }}</td>
            <td class="px-3 py-2 text-ink-muted">
              <div class="max-w-[140px] truncate" :title="intern.mentor">{{ intern.mentor }}</div>
            </td>
            <td class="px-3 py-2 text-right">
              <RouterLink
                v-if="auth.isOperator"
                :to="{ name: 'intern-edit', params: { id: intern.id } }"
                class="text-accent-strong hover:underline"
              >
                Editează
              </RouterLink>
            </td>
          </tr>
          <tr v-if="filteredInterns.length === 0">
            <td colspan="7" class="px-3 py-6 text-center text-ink-muted">Niciun rezultat.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>