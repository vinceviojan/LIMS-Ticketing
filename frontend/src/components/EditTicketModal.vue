<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" persistent>
    <q-card class="ticket-page__dialog">
      <q-card-section class="ticket-page__dialog-head">
        <q-icon name="edit_note" size="26px" color="primary" />
        <span class="ticket-page__dialog-title">Ticket Details</span>
        <q-space />
        <q-btn flat round dense icon="close" @click="closeModal" />
      </q-card-section>

      <q-separator />

      <q-form @submit="submitTicket">
      <q-card-section class="ticket-page__dialog-body">
        <q-input v-model="form.title" label="Subject / Title *" outlined dense class="q-mb-sm"
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
           v-model="form.status"
           :options="statusOptions"
           label="Status"
           outlined dense emit-value map-options class="q-mt-sm"
        />
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
              <q-file v-model="form.upload_intralab" label="Upload New Intralab Attachment" outlined dense clearable accept=".pdf, image/*" />
              <q-btn v-if="ticket?.upload_intralab" flat color="primary" icon="visibility" label="View Intralab Attachment" class="q-mt-xs full-width" @click="viewAttachment('intralab')" />
            </div>
            <div class="col-12 col-md-6">
              <q-file v-model="form.upload_limsportal" label="Upload New LIMS Portal Attachment" outlined dense clearable accept=".pdf, image/*" />
              <q-btn v-if="ticket?.upload_limsportal" flat color="primary" icon="visibility" label="View LIMS Portal Attachment" class="q-mt-xs full-width" @click="viewAttachment('limsportal')" />
            </div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="ticket-page__dialog-actions">
        <q-btn flat no-caps label="Cancel" color="grey-7" @click="closeModal" />
        <q-btn unelevated no-caps type="submit" label="Update Ticket" class="clay-btn clay-btn--primary" :loading="saving" />
      </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../boot/axios'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  ticket: { type: Object, default: () => null },
  mode: { type: String, default: 'view' },
  categoryOptions: { type: Array, default: () => [] },
  staffOptions: { type: Array, default: () => [] },
  priorityOptions: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'update:mode', 'refresh'])

const $q = useQuasar()
const authStore = useAuthStore()

const isAdmin = computed(() => authStore.userRole === 'admin')

const saving = ref(false)
const statusOptions = [
  { label: 'Open', value: 'OPEN' },
  { label: 'Escalated', value: 'ESCALATED' },
  { label: 'Closed', value: 'CLOSE' },
  { label: 'Canceled', value: 'CANCEL' },
]

function emptyForm() {
  return {
    title: '',
    description: '',
    priority: 'NORMAL',
    status: 'OPEN',
    category: null,
    assigned_staff_id: null,
    upload_intralab: null,
    upload_limsportal: null,
  }
}

const form = ref(emptyForm())

watch(() => props.modelValue, (isOpen) => {
  if (isOpen && props.ticket) {
    form.value = {
      title: props.ticket.title || '',
      description: props.ticket.description || '',
      priority: props.ticket.priority || 'NORMAL',
      status: props.ticket.status || 'OPEN',
      category: props.ticket.problem_category_id || null,
      assigned_staff_id: props.ticket.assigned_staff_id || null,
      upload_intralab: null, // intentionally left null for new uploads
      upload_limsportal: null,
    }
  } else if (!isOpen) {
    form.value = emptyForm()
  }
})

function closeModal() {
  emit('update:modelValue', false)
  emit('update:mode', 'view')
}

async function viewAttachment(type) {
  try {
    const id = props.ticket?.real_id || props.ticket?.id
    if (!id) return

    const res = await api.get(`/tickets/${id}/attachment/${type}`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(res.data)
    window.open(url, '_blank')
    setTimeout(() => window.URL.revokeObjectURL(url), 10000)
  } catch (err) {
    console.error('Failed to view attachment', err)
    $q.notify({ type: 'negative', message: 'Failed to access file.' })
  }
}

async function submitTicket() {
  saving.value = true
  try {
    const payload = new FormData()
    payload.append('_method', 'PUT')
    payload.append('issue', form.value.title)
    payload.append('description', form.value.description || '')
    
    if (form.value.category) payload.append('problem_category_id', form.value.category)

    if (isAdmin.value) {
      payload.append('urgency', form.value.priority)
      if (form.value.status) payload.append('status', form.value.status)
      if (form.value.assigned_staff_id) {
        payload.append('assigned_staff_id', form.value.assigned_staff_id)
      } else {
        // Option to unset assigned staff could explicitly pass empty value if handled by backend
      }
    }

    if (form.value.upload_intralab) payload.append('upload_intralab', form.value.upload_intralab)
    if (form.value.upload_limsportal) payload.append('upload_limsportal', form.value.upload_limsportal)

    const id = props.ticket?.real_id || props.ticket?.id
    await api.post(`/tickets/${id}`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    $q.notify({ type: 'positive', message: 'Ticket updated successfully.', position: 'top-right', timeout: 2500 })
    closeModal()
    emit('refresh')
  } catch (err) {
    console.error('Failed to update ticket', err)
    $q.notify({ type: 'negative', message: 'Failed to update ticket. Please check fields.' })
  } finally {
    saving.value = false
  }
}
</script>
