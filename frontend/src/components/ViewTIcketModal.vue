<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
    <q-card class="ticket-page__dialog" style="max-width: 650px; width: 100%;">
      <q-card-section class="ticket-page__dialog-head">
        <q-icon name="visibility" size="26px" color="primary" />
        <span class="ticket-page__dialog-title">Ticket Details</span>
        <q-space />
        <q-btn flat round dense icon="close" @click="closeModal" />
      </q-card-section>

      <q-separator />

      <q-card-section class="ticket-page__dialog-body">
        <div class="ticket-page__form-row">
          <q-input :model-value="ticket?.ticket_no || (ticket?.id ? '#' + ticket.id : '')" label="Ticket #" outlined dense readonly />
          <q-input :model-value="ticket?.created" label="Date Submitted" outlined dense readonly />
        </div>

        <q-input :model-value="ticket?.requester" label="Requester" outlined dense readonly class="q-mt-sm" />

        <q-input :model-value="ticket?.title" label="Subject / Title" outlined dense readonly class="q-mt-sm" />

        <q-input
          :model-value="ticket?.description || 'No description provided.'"
          label="Description"
          outlined dense readonly
          type="textarea" rows="3"
          class="q-mt-sm"
        />

        <div class="ticket-page__form-row q-mt-sm">
          <q-input :model-value="ticket?.priority" label="Priority" outlined dense readonly />
          <q-input :model-value="ticket?.category" label="Category" outlined dense readonly />
        </div>

        <div class="ticket-page__form-row q-mt-sm">
          <q-input :model-value="statusLabel" label="Status" outlined dense readonly />
          <q-input :model-value="ticket?.assignedStaff || 'Unassigned'" label="Assigned Staff" outlined dense readonly />
        </div>

        <q-input
          v-if="ticket?.remarks"
          :model-value="ticket.remarks"
          label="Resolution Remarks"
          outlined dense readonly
          type="textarea" rows="2"
          class="q-mt-md bg-green-50 rounded-borders"
          input-class="text-positive text-weight-medium"
          label-color="positive"
        />

        <!-- ── Attachments List Section ──────────────────────────── -->
        <div class="q-mt-md">
          <div class="text-subtitle2 text-weight-bold q-mb-xs">Attachments</div>
          
          <div v-if="ticket?.attachments && ticket.attachments.length" class="q-gutter-y-xs q-mb-md">
            <q-list bordered separator rounded class="bg-grey-1">
              <q-item v-for="att in ticket.attachments" :key="att.id" dense clickable @click="openAttachment(att)">
                <q-item-section avatar>
                  <q-icon :name="getAttIcon(att)" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-medium text-body2">{{ att.file_name }}</q-item-label>
                  <q-item-label caption v-if="att.file_size">{{ formatBytes(att.file_size) }}</q-item-label>
                  <q-item-label caption v-else-if="att.external_url" class="text-primary">{{ att.external_url }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="open_in_new" size="18px" color="primary" />
                </q-item-section>
              </q-item>
            </q-list>
          </div>

          <!-- Legacy single file attachments fallback -->
          <div v-else-if="ticket?.upload_intralab || ticket?.upload_limsportal" class="row q-col-gutter-sm q-mb-md">
            <div class="col-12 col-md-6" v-if="ticket?.upload_intralab">
              <q-btn flat color="primary" icon="attach_file" label="View Intralab Attachment" class="full-width" @click="viewLegacyAttachment('intralab', ticket.upload_intralab)" />
            </div>
            <div class="col-12 col-md-6" v-if="ticket?.upload_limsportal">
              <q-btn flat color="primary" icon="attach_file" label="View LIMS Portal Attachment" class="full-width" @click="viewLegacyAttachment('limsportal', ticket.upload_limsportal)" />
            </div>
          </div>
          
          <div v-else class="text-grey-6 text-caption text-italic q-py-xs q-mb-md">
            No attachments provided.
          </div>

          <!-- Attachment Upload Area -->
          <div class="row q-col-gutter-sm items-center q-pt-sm" style="border-top: 1px dashed #e2e8f0;" v-if="canAddAttachment()">
            <div class="col">
              <q-file
                v-model="fileToUpload"
                label="Add new attachment (Max 10MB)"
                outlined dense
                accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
              >
                <template v-slot:prepend>
                  <q-icon name="attach_file" />
                </template>
              </q-file>
            </div>
            <div class="col-auto">
              <q-btn
                color="primary"
                icon="upload"
                unelevated
                :loading="uploadingAttachment"
                :disable="!fileToUpload"
                @click="uploadAttachment"
              >
                <q-tooltip>Upload Attachment</q-tooltip>
              </q-btn>
            </div>
          </div>
        </div>

        <!-- ── Rating & Feedback Section (For RESOLVED & CLOSED tickets) ── -->
        <div
          v-if="['CLOSE', 'RESOLVED'].includes(ticket?.status) || ticket?.rating || ticket?.feedback"
          class="q-mt-lg q-pa-md border-radius-8 transition-all"
          :class="!hasExistingRating && isEndUser ? 'bg-amber-1 border-amber-strong shadow-2' : 'bg-amber-1'"
          :style="{ border: !hasExistingRating && isEndUser ? '2px solid #f59e0b' : '1px solid #fef3c7' }"
        >
          <div class="text-subtitle1 text-weight-bolder text-amber-10 q-mb-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
              <q-icon name="rate_review" size="22px" color="amber-9" />
              <span>Service Rating & Feedback</span>
            </div>
            <q-chip v-if="!hasExistingRating && isEndUser" color="amber-9" text-color="white" dense icon="priority_high" class="text-weight-bold" style="font-size: 0.72rem;">
              Action Required
            </q-chip>
          </div>

          <div v-if="hasExistingRating" class="q-mt-sm">
            <div class="row items-center q-mb-xs" v-if="ticket?.rating">
              <span class="text-caption text-grey-8 q-mr-sm text-weight-bold">Rating:</span>
              <q-rating :model-value="Number(ticket.rating)" max="5" size="22px" color="amber-8" icon="star" readonly />
              <span class="text-weight-bold text-caption text-amber-9 q-ml-xs">{{ ticket.rating }} / 5.0</span>
            </div>
            <div class="text-caption text-grey-9 q-mt-xs" v-if="ticket?.feedback">
              <strong>Feedback:</strong> {{ ticket.feedback }}
            </div>
          </div>

          <div v-else-if="isEndUser" class="q-mt-sm">
            <div class="text-caption text-grey-8 q-mb-sm">
              Please rate the service received and leave feedback to help us improve.
            </div>
            <div class="row items-center q-mb-md gap-sm">
              <span class="text-caption text-weight-bold">Star Rating:</span>
              <q-rating v-model="rating" max="5" size="28px" color="amber-8" icon="star" />
              <q-chip dense color="amber-1" text-color="amber-9" class="text-weight-bold" style="font-size: 0.78rem;">
                {{ ratingLabel }}
              </q-chip>
            </div>
            <q-input
              v-model="feedback"
              label="Your Feedback / Review *"
              outlined dense
              type="textarea" rows="3"
              bg-color="white"
              placeholder="Tell us about your experience..."
            />
            <div class="row justify-end q-mt-sm">
              <q-btn
                color="amber-9"
                label="Submit Feedback"
                icon="send"
                unelevated no-caps
                :loading="submittingRating"
                :disable="!rating || !feedback.trim()"
                @click="submitRating"
              />
            </div>
          </div>
          <div v-else class="text-caption text-grey-6 text-italic q-mt-xs">
            No rating or feedback provided yet.
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="ticket-page__dialog-actions">
        <q-btn
          v-if="canResolve"
          unelevated no-caps
          color="positive" outline icon="task_alt" label="Resolve Ticket"
          @click="showResolveDialog = true"
        />
        <q-btn
          v-if="canClaim"
          unelevated no-caps
          color="primary" icon="front_hand" label="Claim / Assign to Me"
          :loading="claiming"
          @click="claimTicket"
        />
        <q-btn flat no-caps label="Close" color="grey-7" @click="closeModal" />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <!-- Right Side Drawer Attachment Preview -->
  <AttachmentPreviewDrawer
    v-model="showDrawer"
    :attachment="selectedAttachment"
  />

  <!-- Resolve Dialog -->
  <q-dialog v-model="showResolveDialog" persistent>
    <q-card style="min-width: 440px; max-width: 90vw; border-radius: 12px;">
      <q-card-section class="row items-center q-pb-xs">
        <div class="text-subtitle1 text-weight-bolder text-positive flex items-center gap-xs">
          <q-icon name="check_circle" size="sm" class="q-mr-xs"/> Resolve Ticket
        </div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>
      
      <q-card-section class="q-pt-sm">
        <div class="text-body2 text-grey-8 q-mb-md">Please provide remarks or details regarding the resolution.</div>
        <q-input
          v-model="resolveRemarks"
          type="textarea"
          outlined dense
          autofocus
          placeholder="E.g. Fixed router settings and restarted..."
          :rules="[val => !!val || 'Remarks are required']"
          bg-color="white"
        />
      </q-card-section>

      <q-card-actions align="right" class="q-px-md q-pb-md text-primary">
        <q-btn flat no-caps label="Cancel" color="grey" v-close-popup />
        <q-btn color="positive" no-caps label="Confirm & Resolve" icon="task_alt" unelevated :loading="resolving" @click="resolveTicket" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, watch, inject } from 'vue'
import { api } from '../boot/axios'
import { useQuasar } from 'quasar'
import AttachmentPreviewDrawer from './AttachmentPreviewDrawer.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  ticket:     { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue', 'refresh'])

const $q = useQuasar()
const authStore = inject('authStore', null)

const rating = ref(5)
const feedback = ref('')
const submittingRating = ref(false)
const claiming = ref(false)
const showDrawer = ref(false)
const selectedAttachment = ref(null)

const showResolveDialog = ref(false)
const resolveRemarks = ref('')
const resolving = ref(false)

const fileToUpload = ref(null)
const uploadingAttachment = ref(false)

const canClaim = computed(() => {
  if (!props.ticket || props.ticket.assigned_staff_id) return false
  const role = authStore?.userRole ? authStore.userRole.toLowerCase() : ''
  return ['staff', 'admin'].includes(role)
})

const canResolve = computed(() => {
  if (!props.ticket || !props.ticket.id) return false
  if (['RESOLVED', 'CLOSE', 'CANCEL'].includes(props.ticket.status)) return false
  const role = authStore?.userRole ? authStore.userRole.toLowerCase() : ''
  if (role === 'admin') return true
  if (role === 'staff' && props.ticket.assigned_staff_id === authStore?.user?.id) return true
  return false
})

const statusLabels = {
  OPEN: 'Open',
  'ON-GOING': 'On-going',
  RESOLVED: 'Resolved',
  ESCALATED: 'Escalated',
  CLOSE: 'Closed',
  CANCEL: 'Canceled',
}

const statusLabel = computed(() => statusLabels[props.ticket?.status] || props.ticket?.status || '—')
const hasExistingRating = computed(() => Boolean(props.ticket?.rating && props.ticket?.feedback))

const ratingTextMap = {
  1: '1 - Poor',
  2: '2 - Fair',
  3: '3 - Good',
  4: '4 - Very Good',
  5: '5 - Excellent',
}
const ratingLabel = computed(() => ratingTextMap[rating.value] || `${rating.value} Stars`)

watch(() => props.ticket, (newVal) => {
  if (newVal) {
    rating.value = newVal.rating || 5
    feedback.value = newVal.feedback || ''
  }
}, { immediate: true })

function closeModal() {
  emit('update:modelValue', false)
}

function getAttIcon(att) {
  if (att.file_type === 'gdrive' || att.external_url) return 'add_link'
  const ext = (att.file_type || att.file_name || '').toLowerCase()
  if (ext.includes('pdf')) return 'picture_as_pdf'
  if (['png', 'jpg', 'jpeg'].some(x => ext.includes(x))) return 'image'
  if (['doc', 'docx'].some(x => ext.includes(x))) return 'description'
  return 'insert_drive_file'
}

function formatBytes(bytes, decimals = 2) {
  if (!bytes) return '0 Bytes'
  const k = 1024
  const dm = decimals < 0 ? 0 : decimals
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
}

async function uploadAttachment() {
  if (!fileToUpload.value) return

  uploadingAttachment.value = true
  const formData = new FormData()
  formData.append('_method', 'PUT')
  formData.append('attachments[0]', fileToUpload.value)

  try {
    const id = props.ticket.real_id || props.ticket.id
    await api.post(`/tickets/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    $q.notify({ type: 'positive', message: 'Attachment uploaded successfully!' })
    fileToUpload.value = null
    emit('refresh')
  } catch (err) {
    console.error('Failed to upload attachment', err)
    $q.notify({ type: 'negative', message: 'Failed to upload attachment.' })
  } finally {
    uploadingAttachment.value = false
  }
}

function canAddAttachment() {
  if (!props.ticket || !props.ticket.id) return false
  if (['RESOLVED', 'CLOSE', 'CANCEL'].includes(props.ticket.status)) return false
  
  const role = authStore?.userRole ? authStore.userRole.toLowerCase() : ''
  return role === 'user'
}

function openAttachment(att) {
  selectedAttachment.value = att
  showDrawer.value = true
}

function viewLegacyAttachment(type, path) {
  if (!props.ticket?.id) return
  const ext = (path || '').split('.').pop()
  openAttachment({
    legacy_type: type,
    ticket_id: props.ticket.id,
    file_name: type === 'intralab' ? 'Intralab Attachment' : 'LIMS Portal Attachment',
    file_type: ext,
  })
}

async function submitRating() {
  if (!rating.value || !feedback.value.trim()) return

  submittingRating.value = true
  try {
    await api.post(`/tickets/${props.ticket.id}/rating`, {
      rating: rating.value,
      feedback: feedback.value.trim(),
    })
  } catch (err) {
    console.error('Failed to submit rating', err)
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'Failed to submit rating.' })
  } finally {
    submittingRating.value = false
  }
}

async function claimTicket() {
  if (!props.ticket?.id && !props.ticket?.real_id) return
  const id = props.ticket.real_id || props.ticket.id

  claiming.value = true
  try {
    const res = await api.post(`/tickets/${id}/assign-self`)
    $q.notify({
      type: 'positive',
      message: res.data?.message || 'Ticket claimed successfully!',
      position: 'top-right'
    })
    emit('refresh')
    closeModal()
  } catch (err) {
    console.error('Failed to claim ticket', err)
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Failed to claim ticket.'
    })
  } finally {
    claiming.value = false
  }
}

async function resolveTicket() {
  if (!resolveRemarks.value || !resolveRemarks.value.trim()) return
  const id = props.ticket.real_id || props.ticket.id
  
  resolving.value = true
  try {
    const res = await api.post(`/tickets/${id}/resolve`, {
      remarks: resolveRemarks.value.trim()
    })
    $q.notify({
      type: 'positive',
      message: res.data?.message || 'Ticket resolved successfully!'
    })
    showResolveDialog.value = false
    emit('refresh')
    closeModal()
  } catch (err) {
    console.error('Failed to resolve ticket', err)
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Failed to resolve ticket.'
    })
  } finally {
    resolving.value = false
  }
}
</script>