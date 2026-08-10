<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" persistent>
    <q-card class="ticket-page__dialog">
      <q-card-section class="ticket-page__dialog-head">
        <q-icon :name="isViewMode ? 'confirmation_number' : 'edit_note'" size="26px" color="primary" />
        <span class="ticket-page__dialog-title">
          {{ isViewMode ? 'Ticket Details' : (isEditMode ? 'Edit Ticket' : 'New Ticket') }}
        </span>
        <q-space />
        <q-btn flat round dense icon="close" @click="closeModal" />
      </q-card-section>

      <q-separator />

      <!-- VIEW MODE -->
      <q-card-section class="ticket-page__dialog-body" v-if="isViewMode && ticket">
        <div class="ticket-detail">
          <div class="ticket-detail__row">
            <span class="ticket-detail__label">Ticket No.</span>
            <span class="ticket-detail__value">{{ ticket.ticket_no || `#${ticket.id}` }}</span>
          </div>
          <div class="ticket-detail__row">
            <span class="ticket-detail__label">Title</span>
            <span class="ticket-detail__value">{{ ticket.title }}</span>
          </div>
          <div class="ticket-detail__row">
            <span class="ticket-detail__label">Requester</span>
            <span class="ticket-detail__value">{{ ticket.requester }}</span>
          </div>
          <div class="ticket-detail__row">
            <span class="ticket-detail__label">Date Submitted</span>
            <span class="ticket-detail__value">{{ ticket.created }}</span>
          </div>
          <div class="ticket-detail__row">
            <span class="ticket-detail__label">Assigned Staff</span>
            <span class="ticket-detail__value">{{ ticket.assignedStaff || 'Unassigned' }}</span>
          </div>
          <div class="ticket-detail__row">
            <span class="ticket-detail__label">Category</span>
            <span class="ticket-detail__value">{{ ticket.category }}</span>
          </div>
          <div class="ticket-detail__row">
            <span class="ticket-detail__label">Priority</span>
            <span class="ticket-detail__value">{{ ticket.priority }}</span>
          </div>
          <div class="ticket-detail__row">
            <span class="ticket-detail__label">Status</span>
            <span :class="['ticket-card__status', `ticket-card__status--${ticket.status?.toLowerCase()}`]">
              {{ ticket.status }}
            </span>
          </div>
          <div class="ticket-detail__row ticket-detail__row--full">
            <span class="ticket-detail__label">Description</span>
            <p class="ticket-detail__desc">{{ ticket.description }}</p>
          </div>
          <div class="ticket-detail__row ticket-detail__row--full">
            <span class="ticket-detail__label">Intralab Attachment</span>
            <a v-if="ticket.upload_intralab" :href="attachmentUrl(ticket.upload_intralab)" target="_blank" rel="noopener">View attachment</a>
            <span v-else class="ticket-detail__value">None</span>
          </div>
          <div class="ticket-detail__row ticket-detail__row--full">
            <span class="ticket-detail__label">LIMS Portal Attachment</span>
            <a v-if="ticket.upload_limsportal" :href="attachmentUrl(ticket.upload_limsportal)" target="_blank" rel="noopener">View attachment</a>
            <span v-else class="ticket-detail__value">None</span>
          </div>
        </div>

        <!-- Status Actions -->
        <div class="ticket-page__status-actions">
          <q-btn unelevated no-caps icon="edit" label="Edit" color="primary" @click="switchToEdit" />
          <q-btn unelevated no-caps icon="priority_high" label="Escalate" color="warning" @click="changeStatus('ESCALATED')" />
          <q-btn unelevated no-caps icon="task_alt" label="Close" color="positive" @click="changeStatus('CLOSE')" />
          <q-btn flat no-caps icon="delete" label="Delete" color="negative" @click="deleteTicket" />
        </div>
      </q-card-section>

      <!-- EDIT / CREATE MODE -->
      <q-card-section class="ticket-page__dialog-body" v-else>
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

      <q-card-actions align="right" class="ticket-page__dialog-actions" v-if="!isViewMode">
        <q-btn flat no-caps label="Cancel" color="grey-7" @click="closeModal" />
        <q-btn unelevated no-caps :label="isEditMode ? 'Save Changes' : 'Submit Ticket'" class="clay-btn clay-btn--primary" :loading="saving" @click="submitTicket" />
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
  ticket: { type: Object, default: null },
  mode: { type: String, default: 'create' }, // 'create', 'view', 'edit'
  categoryOptions: { type: Array, default: () => [] },
  staffOptions: { type: Array, default: () => [] },
  priorityOptions: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'refresh', 'update:mode'])

const $q = useQuasar()
const authStore = useAuthStore()

const isAdmin = computed(() => authStore.userRole === 'admin')

const isViewMode = computed(() => props.mode === 'view')
const isEditMode = computed(() => props.mode === 'edit')
const isCreateMode = computed(() => props.mode === 'create')

const saving = ref(false)
const form = ref({
  title: '',
  description: '',
  priority: 'NORMAL',
  category: null,
  assigned_staff_id: null,
  upload_intralab: null,
  upload_limsportal: null,
})

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    if (isEditMode.value && props.ticket) {
      form.value = {
        title: props.ticket.title,
        description: props.ticket.description,
        priority: props.ticket.priority,
        category: props.ticket.problem_category_id,
        assigned_staff_id: props.ticket.assigned_staff_id,
        upload_intralab: null,
        upload_limsportal: null,
      }
    } else if (isCreateMode.value) {
      form.value = {
        title: '',
        description: '',
        priority: 'NORMAL',
        category: null,
        assigned_staff_id: null,
        upload_intralab: null,
        upload_limsportal: null,
      }
    }
  }
})

function closeModal() {
  emit('update:modelValue', false)
}

function switchToEdit() {
  emit('update:mode', 'edit')
  form.value = {
    title: props.ticket.title,
    description: props.ticket.description,
    priority: props.ticket.priority,
    category: props.ticket.problem_category_id,
    assigned_staff_id: props.ticket.assigned_staff_id,
    upload_intralab: null,
    upload_limsportal: null,
  }
}

async function changeStatus(newStatus) {
  try {
    await api.put(`/tickets/${props.ticket.real_id}`, { status: newStatus })
    $q.notify({ type: 'positive', message: `Ticket ${props.ticket.ticket_no || '#' + props.ticket.id} marked as ${newStatus}`, position: 'top-right', timeout: 2000 })
    closeModal()
    emit('refresh')
  } catch (err) {
    console.error('Failed to update status', err)
    $q.notify({ type: 'negative', message: 'Failed to update ticket status.' })
  }
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
    
    if (isEditMode.value) {
      payload.append('_method', 'PUT')
      await api.post(`/tickets/${props.ticket.real_id}`, payload)
    } else {
      await api.post('/tickets', payload)
    }
    
    $q.notify({ type: 'positive', message: isEditMode.value ? 'Ticket updated successfully.' : 'Ticket submitted successfully.', position: 'top-right', timeout: 2500 })
    closeModal()
    emit('refresh')
  } catch (err) {
    console.error('Failed to save ticket', err)
    $q.notify({ type: 'negative', message: 'Failed to save ticket. Please check fields.' })
  } finally {
    saving.value = false
  }
}

function deleteTicket() {
  $q.dialog({
    title: 'Delete ticket?',
    message: `Delete ${props.ticket.ticket_no || `#${props.ticket.id}`} permanently?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`/tickets/${props.ticket.real_id}`)
      closeModal()
      emit('refresh')
      $q.notify({ type: 'positive', message: 'Ticket deleted successfully.' })
    } catch (err) {
      console.error('Failed to delete ticket', err)
      $q.notify({ type: 'negative', message: 'Failed to delete ticket.' })
    }
  })
}

function attachmentUrl(path) {
  const baseUrl = (import.meta.env.VITE_API_URL || '').replace(/\/api\/?$/, '')
  return `${baseUrl}/storage/${path}`
}
</script>

<style scoped>
/* Scoped styles if any. Uses global classes from TicketManagementPage.scss for now */
</style>
