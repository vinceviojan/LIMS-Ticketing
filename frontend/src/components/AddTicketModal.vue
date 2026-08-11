<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" persistent>
    <q-card class="ticket-page__dialog">
      <q-card-section class="ticket-page__dialog-head">
        <q-icon name="edit_note" size="26px" color="primary" />
        <span class="ticket-page__dialog-title">New Ticket</span>
        <q-space />
        <q-btn flat round dense icon="close" @click="closeModal" />
      </q-card-section>

      <q-separator />

      <q-form @submit="submitTicket">
      <q-card-section class="ticket-page__dialog-body">
        <!-- ── Requester (auto-filled, read-only) ────────────── -->
        <div class="ticket-page__form-row">
          <q-input :model-value="requesterName" label="Requester" outlined dense readonly />
          <q-input :model-value="requesterEmail" label="Email" outlined dense readonly />
        </div>
        <q-input :model-value="requesterOffice" label="Division / Office" outlined dense readonly class="q-mt-sm" />

        <q-input v-model="form.title" label="Subject / Title *" outlined dense class="q-mt-sm q-mb-sm"
          :rules="[val => !!val || 'Required']" />
        <q-input v-model="form.description" label="Description *" outlined dense type="textarea" rows="3" class="q-mb-sm"
          :rules="[val => !!val || 'Required']" />
        <div class="ticket-page__form-row">
          <q-select
            v-model="form.priority"
            :options="priorityOptions"
            label="Priority"
            outlined dense emit-value map-options
            :disable="!isAdmin"
          />
          <q-select v-model="form.category" :options="categoryOptions" label="Category" outlined dense emit-value map-options />
        </div>
        <q-select
          v-if="isAdmin"
          v-model="form.assigned_staff_id"
          :options="staffOptions"
          label="Assign Staff"
          outlined dense clearable emit-value map-options class="q-mt-sm"
        />
        <div class="q-mt-md">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-file v-model="form.upload_intralab" label="Upload Intralab Attachment" outlined dense clearable accept=".pdf, image/*" />
            </div>
            <div class="col-12 col-md-6">
              <q-file v-model="form.upload_limsportal" label="Upload LIMS Portal Attachment" outlined dense clearable accept=".pdf, image/*" />
            </div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="ticket-page__dialog-actions">
        <q-btn flat no-caps label="Cancel" color="grey-7" @click="closeModal" />
        <q-btn unelevated no-caps type="submit" label="Submit Ticket" class="clay-btn clay-btn--primary" :loading="saving" />
      </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, watch, inject } from 'vue'
import { api } from '../boot/axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const authStore = inject('authStore')

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  categoryOptions: { type: Array, default: () => [] },
  staffOptions: { type: Array, default: () => [] },
  priorityOptions: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'refresh'])

const isAdmin = computed(() => authStore.userRole === 'admin')

// ── Auto-filled requester info from the logged-in user ─────────
// Adjust the property names below (authStore.user.*) to match your
// actual auth store / user object shape if they differ.
const requesterName = computed(() => {
  const u = authStore.user || {}
  return u.full_name || `${u.first_name || ''} ${u.last_name || ''}`.trim() || '—'
})
const requesterEmail = computed(() => authStore.user?.email || '—')
const requesterOffice = computed(() => authStore.user?.office || authStore.user?.division || '—')

const saving = ref(false)

function emptyForm() {
  return {
    title: '',
    description: '',
    priority: 'NORMAL',
    category: null,
    assigned_staff_id: null,
    upload_intralab: null,
    upload_limsportal: null,
  }
}

const form = ref(emptyForm())

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    form.value = emptyForm()
  }
})

function closeModal() {
  emit('update:modelValue', false)
}

async function submitTicket() {
  saving.value = true
  try {
    const payload = new FormData()
    payload.append('issue', form.value.title)
    payload.append('description', form.value.description || '')
    if (form.value.category) payload.append('problem_category_id', form.value.category)

    if (isAdmin.value) {
      payload.append('urgency', form.value.priority)
      if (form.value.assigned_staff_id) payload.append('assigned_staff_id', form.value.assigned_staff_id)
    }

    if (form.value.upload_intralab) payload.append('upload_intralab', form.value.upload_intralab)
    if (form.value.upload_limsportal) payload.append('upload_limsportal', form.value.upload_limsportal)

    await api.post('/tickets', payload)

    $q.notify({ type: 'positive', message: 'Ticket submitted successfully.', position: 'top-right', timeout: 2500 })
    closeModal()
    emit('refresh')
  } catch (err) {
    console.error('Failed to save ticket', err.response?.data || err)
    const msg = err.response?.data?.message || 'Failed to save ticket. Please check fields.'
    let errs = ''
    if (err.response?.data?.errors) {
      errs = Object.values(err.response.data.errors).flat().join(' ')
    }
    $q.notify({ type: 'negative', message: msg + ' ' + errs })
  } finally {
    saving.value = false
  }
}
</script>