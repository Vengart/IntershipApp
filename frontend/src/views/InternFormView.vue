<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch } from '@/lib/api'

const props = defineProps({
  id: { type: [String, Number], default: null },
})

const router = useRouter()
const isEditMode = computed(() => props.id !== null)

const directions = ref([])
const sections = ref([])
const isLoading = ref(true)
const isSaving = ref(false)
const errorMessage = ref('')

const form = ref({
  full_name: '',
  email: '',
  phone: '',
  university: '',
  faculty: '',
  specialty: '',
  study_year: '',
  direction_id: '',
  section_id: '',
  internship_status: '',
  start_date: '',
  end_date: '',
  mentor: '',
  internship_type: '',
  recommendation_source: '',
  hiring_potential: '',
  is_active: true,
})

// Секции сужаются под выбранное направление — если сменили направление,
// а старая секция ему не принадлежит, сбрасываем выбор
const availableSections = computed(() =>
  sections.value.filter((s) => String(s.direction_id) === String(form.value.direction_id)),
)

async function loadReferenceData() {
  const [directionsData, sectionsData] = await Promise.all([
    apiFetch('/directions'),
    apiFetch('/sections'),
  ])
  directions.value = directionsData
  sections.value = sectionsData
}

async function loadIntern() {
  // GET /interns не имеет отдельного /interns/{id}, поэтому берём из общего списка —
  // для формы редактирования этого достаточно на таком масштабе данных
  const all = await apiFetch('/interns')
  const intern = all.find((i) => String(i.id) === String(props.id))
  if (!intern) {
    errorMessage.value = 'Practicantul nu a fost găsit'
    return
  }
  Object.keys(form.value).forEach((key) => {
    if (key in intern) form.value[key] = intern[key]
  })
}

onMounted(async () => {
  isLoading.value = true
  try {
    await loadReferenceData()
    if (isEditMode.value) await loadIntern()
  } catch (e) {
    errorMessage.value = e.message
  } finally {
    isLoading.value = false
  }
})

// Телефон храним в form.phone целиком как "(+373)XXXXXXXX" (формат уже есть
// в существующих данных) — но пользователь должен вводить только цифры,
// префикс "(+373)" статичный и не редактируется.
const phoneDigits = computed(() => form.value.phone.replace(/\D/g, ''))

function handlePhoneInput(event) {
  const digitsOnly = event.target.value.replace(/\D/g, '')
  form.value.phone = digitsOnly ? `(+373)${digitsOnly}` : ''
  // v-bind вместо v-model — если цифры не поменялись по значению, но юзер
  // ввёл букву, инпут не перерисуется сам, форсируем синхронизацию значения
  event.target.value = digitsOnly
}

// Textarea растёт по высоте под контент вместо горизонтальной прокрутки в одну строку
function autoResize(event) {
  event.target.style.height = 'auto'
  event.target.style.height = `${event.target.scrollHeight}px`
}

async function handleSubmit() {
  isSaving.value = true
  errorMessage.value = ''
  try {
    if (isEditMode.value) {
      await apiFetch(`/interns/${props.id}`, {
        method: 'PUT',
        body: JSON.stringify(form.value),
      })
    } else {
      await apiFetch('/interns', {
        method: 'POST',
        body: JSON.stringify(form.value),
      })
    }
    router.push({ name: 'interns-list' })
  } catch (e) {
    errorMessage.value = e.message
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl">
    <h1 class="font-display text-2xl text-ink mb-6">
      {{ isEditMode ? 'Editare practicant' : 'Practicant nou' }}
    </h1>

    <p v-if="isLoading" class="text-ink-muted text-sm">Se încarcă…</p>

    <form v-else @submit.prevent="handleSubmit" class="bg-surface border border-border rounded-md p-6 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
          <label class="block text-sm text-ink-muted mb-1">Nume complet *</label>
          <input v-model="form.full_name" required maxlength="150" type="text" class="field" />
        </div>

        <div>
          <label class="block text-sm text-ink-muted mb-1">Email</label>
          <input v-model="form.email" maxlength="100" type="email" class="field" />
        </div>
        <div>
          <label class="block text-sm text-ink-muted mb-1">Telefon</label>
          <div class="flex items-center gap-2">
            <span class="text-ink-muted text-sm font-mono select-none">(+373)</span>
            <input
              :value="phoneDigits"
              @input="handlePhoneInput"
              type="text"
              inputmode="numeric"
              maxlength="9"
              placeholder="69879234"
              class="field font-mono"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm text-ink-muted mb-1">Universitate</label>
          <input v-model="form.university" maxlength="150" type="text" class="field" />
        </div>
        <div>
          <label class="block text-sm text-ink-muted mb-1">Facultate</label>
          <input v-model="form.faculty" maxlength="150" type="text" class="field" />
        </div>

        <div>
          <label class="block text-sm text-ink-muted mb-1">Specialitate</label>
          <input v-model="form.specialty" maxlength="150" type="text" class="field" />
        </div>
        <div>
          <label class="block text-sm text-ink-muted mb-1">An studiu</label>
          <input v-model="form.study_year" maxlength="50" type="text" class="field" />
        </div>

        <div>
          <label class="block text-sm text-ink-muted mb-1">Direcție *</label>
          <select v-model="form.direction_id" required class="field">
            <option value="" disabled>Alege…</option>
            <option v-for="d in directions" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-ink-muted mb-1">Secție *</label>
          <select v-model="form.section_id" required class="field">
            <option value="" disabled>Alege…</option>
            <option v-for="s in availableSections" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm text-ink-muted mb-1">Data început</label>
          <input v-model="form.start_date" type="date" class="field" />
        </div>
        <div>
          <label class="block text-sm text-ink-muted mb-1">Data sfârșit</label>
          <input v-model="form.end_date" type="date" class="field" />
        </div>

        <div>
          <label class="block text-sm text-ink-muted mb-1">Mentor</label>
          <input v-model="form.mentor" maxlength="150" type="text" class="field" />
        </div>
        <div>
          <label class="block text-sm text-ink-muted mb-1">Status stagiu</label>
          <input v-model="form.internship_status" maxlength="50" type="text" class="field" />
        </div>

        <div>
          <label class="block text-sm text-ink-muted mb-1">Tip stagiu</label>
          <input v-model="form.internship_type" maxlength="50" type="text" class="field" />
        </div>
        <div>
          <label class="block text-sm text-ink-muted mb-1">Potențial angajare</label>
          <input v-model="form.hiring_potential" maxlength="50" type="text" class="field" />
        </div>

        <div class="col-span-2">
          <label class="block text-sm text-ink-muted mb-1">Sursă recomandare</label>
          <textarea
            v-model="form.recommendation_source"
            @input="autoResize"
            maxlength="150"
            rows="1"
            class="field resize-none overflow-hidden"
          ></textarea>
        </div>

        <div v-if="isEditMode" class="col-span-2">
          <label class="flex items-center gap-2 text-sm text-ink-muted">
            <input v-model="form.is_active" type="checkbox" class="accent-accent" />
            Activ
          </label>
        </div>
      </div>

      <p v-if="errorMessage" class="text-danger text-sm">{{ errorMessage }}</p>

      <div class="flex gap-2 pt-2">
        <button
          type="submit"
          :disabled="isSaving"
          class="px-4 py-2 bg-accent hover:bg-accent-strong text-white rounded text-sm disabled:opacity-50"
        >
          {{ isSaving ? 'Se salvează…' : 'Salvează' }}
        </button>
        <RouterLink
          :to="{ name: 'interns-list' }"
          class="px-4 py-2 border border-border rounded text-sm text-ink-muted hover:text-ink"
        >
          Anulează
        </RouterLink>
      </div>
    </form>
  </div>
</template>

<style scoped>
.field {
  width: 100%;
  background-color: var(--color-canvas);
  border: 1px solid var(--color-border);
  border-radius: 0.25rem;
  padding: 0.5rem 0.75rem;
  color: var(--color-ink);
}
.field:focus {
  outline: none;
  box-shadow: 0 0 0 2px var(--color-accent-strong);
}
</style>
