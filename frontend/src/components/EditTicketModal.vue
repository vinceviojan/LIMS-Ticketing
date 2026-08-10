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
          v-model="form.assigned_staff_id"
          :options="staffOptions"
          label="Assign Staff"
          outlined dense clearable emit-value map-options class="q-mt-sm"
        />
        <div class="ticket-page__form-row q-mt-sm">
          <q-file v-model="form.upload_intralab" label="Intralab Attachment" outlined dense clearable />
          <q-file v-model="form.upload_limsportal" label="LIMS Portal Attachment" outlined dense clearable />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="ticket-page__dialog-actions">
        <q-btn flat no-caps label="Cancel" color="grey-7" @click="closeModal" />
        <q-btn unelevated no-caps label="Submit Ticket" class="clay-btn clay-btn--primary" :loading="saving" @click="submitTicket" />
      </q-card-actions>
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
  categoryOptions: { type: Array, default: () => [] },
  staffOptions: { type: Array, default: () => [] },
  priorityOptions: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'refresh'])

const $q = useQuasar()
const authStore = useAuthStore()

const isAdmin = computed(() => authStore.userRole === 'admin')

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
    console.error('Failed to save ticket', err)
    $q.notify({ type: 'negative', message: 'Failed to save ticket. Please check fields.' })
  } finally {
    saving.value = false
  }
}
</script>
