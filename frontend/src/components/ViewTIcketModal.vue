<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    persistent
    transition-show="scale"
    transition-hide="scale"
  >
    <q-card
      class="ticket-page__dialog user-view-modal"
      style="width: 820px; max-width: 95vw; border-radius: 16px"
    >
      <!-- ── Header ─────────────────────────────────────────────── -->
      <q-card-section
        class="user-view-modal__head bg-white q-pa-md row items-center justify-between border-bottom"
      >
        <div class="row items-center gap-sm">
          <div class="user-view-modal__icon-bg">
            <q-icon name="confirmation_number" size="22px" color="primary" />
          </div>
          <div>
            <div class="text-subtitle1 text-weight-bold text-grey-10 line-height-tight">
              Ticket Details
            </div>
            <div class="text-caption text-grey-6">View ticket information and history</div>
          </div>
        </div>
        <q-btn flat round dense icon="close" color="grey-6" @click="closeModal" />
      </q-card-section>

      <q-separator />

      <!-- ── Body ────────────────────────────────────────────────── -->
      <q-card-section
        class="user-view-modal__body q-pa-lg"
        style="max-height: 65vh; overflow-y: auto"
      >
        <!-- 1st Row: Ticket # -->
        <div class="q-mb-md">
          <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Ticket #</label>
          <q-input
            :model-value="ticket?.ticket_no || (ticket?.id ? '#' + ticket.id : '—')"
            outlined
            dense
            readonly
            bg-color="grey-1"
            input-class="text-weight-bold text-grey-9"
          >
            <template #prepend>
              <q-icon name="tag" color="primary" size="20px" />
            </template>
          </q-input>
        </div>

        <!-- 2nd Row: Status & Priority (Color-coded text) -->
        <div class="row q-col-gutter-md q-mb-md">
          <!-- Status -->
          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Status</label>
            <q-input
              :model-value="statusLabel"
              outlined
              dense
              readonly
              bg-color="grey-1"
              :input-style="{ color: statusColor, fontWeight: '700' }"
            >
              <template #prepend>
                <q-icon name="info" :style="{ color: statusColor }" size="20px" />
              </template>
            </q-input>
          </div>

          <!-- Priority -->
          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Priority</label>
            <q-input
              :model-value="displayPriority"
              outlined
              dense
              readonly
              bg-color="grey-1"
              :input-style="{ color: priorityColor, fontWeight: '700' }"
            >
              <template #prepend>
                <q-icon name="flag" :style="{ color: priorityColor }" size="20px" />
              </template>
            </q-input>
          </div>
        </div>

        <!-- 3rd Row: Requester & Date Submitted -->
        <div class="row q-col-gutter-md q-mb-md">
          <!-- Requester -->
          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Requester</label>
            <q-input :model-value="displayRequester" outlined dense readonly bg-color="grey-1">
              <template #prepend>
                <q-icon name="person" color="primary" size="20px" />
              </template>
            </q-input>
          </div>

          <!-- Date Submitted -->
          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs"
              >Date Submitted / Target Date</label
            >
            <div class="row items-center gap-sm">
              <q-input
                :model-value="displayCreated"
                outlined
                dense
                readonly
                bg-color="grey-1"
                class="col"
              >
                <template #prepend>
                  <q-icon name="event" color="primary" size="20px" />
                </template>
              </q-input>
              <q-input
                v-if="ticket?.target_resolution_date"
                :model-value="displayTargetDate"
                outlined
                dense
                readonly
                bg-color="orange-1"
                class="col"
                :input-style="{ color: '#c2410c', fontWeight: '700' }"
              >
                <template #prepend>
                  <q-icon name="event_available" color="orange-9" size="20px" />
                </template>
              </q-input>
            </div>
          </div>
        </div>

        <!-- 4th Row: Subject / Title & Category -->
        <div class="row q-col-gutter-md q-mb-md">
          <!-- Subject / Title -->
          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs"
              >Subject / Title</label
            >
            <q-input :model-value="displayTitle" outlined dense readonly bg-color="grey-1">
              <template #prepend>
                <q-icon name="subtitles" color="primary" size="20px" />
              </template>
            </q-input>
          </div>

          <!-- Category -->
          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Category</label>
            <q-input :model-value="displayCategory" outlined dense readonly bg-color="grey-1">
              <template #prepend>
                <q-icon name="category" color="primary" size="20px" />
              </template>
            </q-input>
          </div>
        </div>

        <!-- 5th Row: Description -->
        <div class="q-mb-md">
          <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Description</label>
          <q-input
            :model-value="displayDescription"
            outlined
            dense
            readonly
            type="textarea"
            rows="4"
            bg-color="grey-1"
          >
            <template #prepend>
              <q-icon name="notes" color="primary" size="20px" />
            </template>
          </q-input>
        </div>

        <!-- Resolution & Remarks (if resolved) -->
        <div v-if="ticket?.status === 'RESOLVED' || ticket?.status === 'CLOSE'" class="q-mb-md">
          <label class="form-label text-weight-bold text-positive block q-mb-xs">Resolution</label>
          <q-input
            :model-value="displayResolution"
            outlined
            dense
            readonly
            type="textarea"
            rows="2"
            bg-color="green-1"
            input-class="text-positive text-weight-medium"
          >
            <template #prepend>
              <q-icon name="task_alt" color="positive" size="20px" />
            </template>
          </q-input>

          <div class="row q-mt-xs text-caption text-grey-8 justify-between">
            <div v-if="displayApprovedBy" class="q-mb-xs">
              <q-icon name="verified_user" color="grey-6" size="16px" class="q-mr-xs" />
              Approved/Closed By: <span class="text-weight-bold">{{ displayApprovedBy }}</span>
            </div>
            <div v-if="ticket?.date_action" class="q-mb-xs">
              <q-icon name="history" color="grey-6" size="16px" class="q-mr-xs" />
              Date of Action: <span class="text-weight-bold">{{ displayDateAction }}</span>
            </div>
            <div v-if="ticket?.date_closed" class="q-mb-xs">
              <q-icon name="event_available" color="grey-6" size="16px" class="q-mr-xs" />
              Date Closed: <span class="text-weight-bold">{{ displayDateClosed }}</span>
            </div>
          </div>
        </div>

        <!-- Final Remarks -->
        <div v-if="ticket?.final_remarks" class="q-mb-md">
          <label class="form-label text-weight-bold text-grey-9 block q-mb-xs"
            >Final Remarks (Admin)</label
          >
          <q-input
            :model-value="ticket.final_remarks"
            outlined
            dense
            readonly
            type="textarea"
            rows="2"
            bg-color="grey-3"
            input-class="text-grey-9 text-weight-medium"
          >
            <template #prepend>
              <q-icon name="admin_panel_settings" color="grey-7" size="20px" />
            </template>
          </q-input>
        </div>

        <!-- 6th / Last Row: Attachments -->
        <div class="q-mt-lg">
          <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Attachments</label>

          <div v-if="ticket?.attachments && ticket.attachments.length" class="q-mb-md">
            <div class="row q-col-gutter-sm">
              <div v-for="att in ticket.attachments" :key="att.id" class="col-12 col-sm-6">
                <div
                  class="file-item row items-center justify-between q-pa-sm bg-grey-2 rounded-borders cursor-pointer"
                  @click="openAttachment(att)"
                >
                  <div class="row items-center gap-sm col ellipsis">
                    <q-avatar
                      size="32px"
                      color="primary"
                      text-color="white"
                      :icon="getAttIcon(att)"
                    />
                    <div class="col ellipsis">
                      <div class="text-weight-medium text-body2 ellipsis">{{ att.file_name }}</div>
                      <div class="text-caption text-grey-7" v-if="att.file_size">
                        {{ formatBytes(att.file_size) }}
                      </div>
                      <div class="text-caption text-primary ellipsis" v-else-if="att.external_url">
                        {{ att.external_url }}
                      </div>
                    </div>
                  </div>
                  <q-btn flat round dense icon="open_in_new" color="primary" size="sm" />
                </div>
              </div>
            </div>
          </div>

          <!-- Legacy single file attachments fallback -->
          <div
            v-else-if="ticket?.upload_intralab || ticket?.upload_limsportal"
            class="row q-col-gutter-sm q-mb-md"
          >
            <div class="col-12 col-md-6" v-if="ticket?.upload_intralab">
              <q-btn
                flat
                color="primary"
                icon="attach_file"
                label="View Intralab Attachment"
                class="full-width"
                @click="viewLegacyAttachment('intralab', ticket.upload_intralab)"
              />
            </div>
            <div class="col-12 col-md-6" v-if="ticket?.upload_limsportal">
              <q-btn
                flat
                color="primary"
                icon="attach_file"
                label="View LIMS Portal Attachment"
                class="full-width"
                @click="viewLegacyAttachment('limsportal', ticket.upload_limsportal)"
              />
            </div>
          </div>

          <div v-else class="text-grey-6 text-caption italic q-py-xs q-mb-md">
            No attachments provided.
          </div>
        </div>

        <!-- ── Rating & Feedback Section (For RESOLVED & CLOSED tickets) ── -->
        <div
          v-if="
            ['CLOSE', 'RESOLVED'].includes(ticket?.status) || ticket?.rating || ticket?.feedback
          "
          class="q-mt-lg q-pa-md rounded-borders"
          :class="!hasExistingRating && isEndUser ? 'bg-amber-1 border-amber' : 'bg-amber-1'"
          :style="{
            border: !hasExistingRating && isEndUser ? '2px solid #f59e0b' : '1px solid #fef3c7',
          }"
        >
          <div
            class="text-subtitle1 text-weight-bolder text-amber-10 q-mb-xs flex items-center justify-between"
          >
            <div class="flex items-center gap-xs">
              <q-icon name="rate_review" size="22px" color="amber-9" />
              <span>Service Rating & Feedback</span>
            </div>
            <q-chip
              v-if="!hasExistingRating && isEndUser"
              color="amber-9"
              text-color="white"
              dense
              icon="priority_high"
              class="text-weight-bold"
              style="font-size: 0.72rem"
            >
              Action Required
            </q-chip>
          </div>

          <div v-if="hasExistingRating" class="q-mt-sm">
            <div class="row items-center q-mb-xs" v-if="ticket?.rating">
              <span class="text-caption text-grey-8 q-mr-sm text-weight-bold">Rating:</span>
              <q-rating
                :model-value="Number(ticket.rating)"
                max="5"
                size="22px"
                color="amber-8"
                icon="star"
                readonly
              />
              <span class="text-weight-bold text-caption text-amber-9 q-ml-xs"
                >{{ ticket.rating }} / 5.0</span
              >
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
              <q-chip
                dense
                color="amber-1"
                text-color="amber-9"
                class="text-weight-bold"
                style="font-size: 0.78rem"
              >
                {{ ratingLabel }}
              </q-chip>
            </div>
            <q-input
              v-model="feedback"
              label="Your Feedback / Review *"
              outlined
              dense
              type="textarea"
              rows="3"
              bg-color="white"
              placeholder="Tell us about your experience..."
            />
            <div class="row justify-end q-mt-sm">
              <q-btn
                color="amber-9"
                label="Submit Feedback"
                icon="send"
                unelevated
                no-caps
                :loading="submittingRating"
                :disable="!rating || !feedback.trim()"
                @click="submitRating"
              />
            </div>
          </div>
          <div v-else class="text-caption text-grey-6 italic q-mt-xs">
            No rating or feedback provided yet.
          </div>
        </div>
      </q-card-section>

      <!-- ── Footer Actions (Only show for Resolve / Claim actions if applicable) ── -->
      <template v-if="canResolve || canClaim">
        <q-separator />
        <q-card-actions align="right" class="q-pa-md bg-grey-1 gap-sm">
          <q-btn
            v-if="canResolve"
            unelevated
            no-caps
            color="positive"
            outline
            icon="task_alt"
            label="Resolve Ticket"
            class="text-weight-medium"
            @click="showResolveDialog = true"
          />
          <q-btn
            v-if="canClaim"
            unelevated
            no-caps
            color="primary"
            icon="front_hand"
            label="Claim / Assign to Me"
            class="text-weight-medium"
            :loading="claiming"
            @click="claimTicket"
          />
        </q-card-actions>
      </template>
    </q-card>
  </q-dialog>

  <!-- Right Side Drawer Attachment Preview -->
  <AttachmentPreviewDrawer v-model="showDrawer" :attachment="selectedAttachment" />

  <!-- Resolve Dialog -->
  <q-dialog v-model="showResolveDialog" persistent transition-show="scale" transition-hide="scale">
    <q-card
      class="resolve-card"
      style="width: 580px; max-width: 94vw; border-radius: 16px; overflow: hidden"
    >
      <!-- Card Header -->
      <q-card-section class="q-pa-md bg-white border-bottom row items-center justify-between">
        <div class="row items-center gap-sm">
          <div class="resolve-icon-wrap">
            <q-icon name="task_alt" size="22px" color="positive" />
          </div>
          <div>
            <div
              class="text-subtitle1 text-weight-bold text-grey-10 line-height-tight flex items-center gap-xs"
            >
              <span>Resolve Ticket</span>
              <q-badge
                color="green-2"
                text-color="green-9"
                class="text-weight-bold q-px-sm q-py-xs"
                style="font-size: 0.72rem; border-radius: 6px"
              >
                {{ ticket?.ticket_no || (ticket?.id ? '#' + ticket.id : '') }}
              </q-badge>
            </div>
            <div class="text-caption text-grey-6" style="font-size: 0.78rem">
              Select a quick pick template or type resolution remarks below.
            </div>
          </div>
        </div>
        <q-btn flat round dense icon="close" color="grey-6" v-close-popup />
      </q-card-section>

      <!-- Card Body -->
      <q-card-section class="q-pa-md bg-grey-1">
        <!-- Quick Picks Section -->
        <div class="q-mb-md">
          <div class="row items-center justify-between q-mb-xs">
            <div class="text-caption text-weight-bold text-grey-8 flex items-center gap-xs">
              <span>Quick Pick Templates</span>
              <span class="text-caption text-grey-5 font-normal q-ml-xs"
                >(Click to add or remove)</span
              >
            </div>
            <q-btn
              v-if="resolveResolution"
              flat
              dense
              no-caps
              color="negative"
              size="xs"
              icon="cleaning_services"
              label="Clear All"
              class="q-px-xs text-weight-bold"
              @click="resolveResolution = ''"
            />
          </div>

          <!-- Quick Pick Chips Grid -->
          <div class="quick-picks-container q-pa-xs">
            <button
              v-for="item in PREDEFINED_RESOLUTIONS"
              :key="item.label"
              type="button"
              class="quick-pick-btn"
              :class="{ 'quick-pick-btn--active': resolveResolution.includes(item.value) }"
              @click="applyPredefined(item.value)"
            >
              <q-icon
                :name="resolveResolution.includes(item.value) ? 'check_circle' : item.icon"
                size="14px"
                class="q-mr-xs"
              />
              <span>{{ item.label }}</span>
            </button>
          </div>
        </div>

        <!-- Resolution Textarea -->
        <div>
          <div class="row items-center justify-between q-mb-xs">
            <label class="text-caption text-weight-bold text-grey-9 block">
              Resolution / Remarks <span class="text-negative">*</span>
            </label>
            <span class="text-caption text-grey-6" style="font-size: 0.74rem"
              >Visible to ticket requester</span
            >
          </div>

          <q-input
            v-model="resolveResolution"
            type="textarea"
            rows="3"
            outlined
            dense
            autofocus
            placeholder="E.g. Provision of technical equipment granted..."
            :rules="[(val) => (!!val && !!val.trim()) || 'Resolution details are required']"
            bg-color="white"
            class="resolve-textarea"
          />
        </div>
      </q-card-section>

      <q-separator />

      <!-- Card Actions -->
      <q-card-actions align="right" class="q-pa-md bg-white gap-sm">
        <q-btn flat no-caps label="Cancel" color="grey-7" v-close-popup />
        <q-btn
          color="positive"
          no-caps
          label="Confirm & Resolve"
          icon="task_alt"
          unelevated
          class="text-weight-bold border-radius-8 q-px-md"
          :loading="resolving"
          :disable="!resolveResolution || !resolveResolution.trim()"
          @click="resolveTicket"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { api } from '../boot/axios'
import { useQuasar } from 'quasar'
import AttachmentPreviewDrawer from './AttachmentPreviewDrawer.vue'
import { useAuthStore } from '../stores/auth'
import { PREDEFINED_RESOLUTIONS } from '../constants/resolutions'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  ticket: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue', 'refresh'])

const $q = useQuasar()
const authStore = useAuthStore()

const rating = ref(5)
const feedback = ref('')
const submittingRating = ref(false)
const claiming = ref(false)
const showDrawer = ref(false)
const selectedAttachment = ref(null)

const showResolveDialog = ref(false)
const resolveResolution = ref('')
const resolving = ref(false)

function applyPredefined(val) {
  if (!val) return
  const current = resolveResolution.value || ''
  if (!current.trim()) {
    resolveResolution.value = val
  } else {
    if (current.includes(val)) {
      // Toggle off if already present
      const lines = current.split('\n').filter((l) => l.trim() !== val.trim())
      resolveResolution.value = lines.join('\n').trim()
    } else {
      // Append if not present
      resolveResolution.value = `${current.trim()}\n${val}`
    }
  }
}

watch(showResolveDialog, (newVal) => {
  if (newVal) {
    resolveResolution.value = ''
  }
})

const isEndUser = computed(() => {
  const role = authStore?.userRole ? authStore.userRole.toLowerCase() : ''
  return role === 'user'
})

const canClaim = computed(() => {
  const ticket = props.ticket
  if (!ticket) return false
  // For unassigned tickets, assigned_staff_id should be null
  if (ticket.assigned_staff_id !== null && ticket.assigned_staff_id !== undefined) return false
  const role = (authStore.userRole || '').toLowerCase()
  console.log(
    '[ViewTicketModal] canClaim check → role:',
    role,
    '| assigned_staff_id:',
    ticket.assigned_staff_id,
    '| ticket:',
    ticket,
  )
  return ['staff', 'admin'].includes(role)
})

const canResolve = computed(() => {
  const ticket = props.ticket
  if (!ticket || !ticket.id) return false
  if (['RESOLVED', 'CLOSE', 'CANCEL'].includes(ticket.status)) return false
  const role = (authStore.userRole || '').toLowerCase()
  if (role === 'admin') return true
  if (role === 'staff' && ticket.assigned_staff_id === authStore.user?.id) return true
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

const statusLabel = computed(
  () => statusLabels[props.ticket?.status] || props.ticket?.status || '—',
)

const displayPriority = computed(() => props.ticket?.priority || props.ticket?.urgency || 'NORMAL')
const displayRequester = computed(() => props.ticket?.requester || props.ticket?.user?.name || '—')
const displayCreated = computed(() => props.ticket?.created || props.ticket?.created_at || '—')
const displayTitle = computed(() => props.ticket?.title || props.ticket?.issue || '—')
const displayCategory = computed(
  () => props.ticket?.category || props.ticket?.problem_category?.categories || 'Uncategorized',
)
const displayDescription = computed(
  () => props.ticket?.description || props.ticket?.details || 'No description provided.',
)
const displayResolution = computed(
  () => props.ticket?.resolution || props.ticket?.remarks || 'No resolution recorded.',
)
const displayApprovedBy = computed(
  () =>
    props.ticket?.approved_by ||
    (props.ticket?.approvedBy?.first_name
      ? `${props.ticket.approvedBy.first_name} ${props.ticket.approvedBy.last_name}`
      : ''),
)
const displayDateAction = computed(() => props.ticket?.date_action || '—')
const displayDateClosed = computed(() => props.ticket?.date_closed || '—')
const displayTargetDate = computed(() => props.ticket?.target_resolution_date || '—')

// Color-coded text for status
const statusColor = computed(() => {
  const s = props.ticket?.status?.toUpperCase() || ''
  if (s === 'OPEN') return '#c2410c'
  if (s === 'ON-GOING') return '#1d4ed8'
  if (s === 'PENDING') return '#ca8a04'
  if (s === 'ESCALATED') return '#9333ea'
  if (s === 'RESOLVED') return '#16a34a'
  if (s === 'CLOSE' || s === 'CLOSED') return '#475569'
  if (s === 'CANCEL' || s === 'CANCELED') return '#dc2626'
  return '#475569'
})

// Color-coded text for priority
const priorityColor = computed(() => {
  const p = displayPriority.value.toUpperCase()
  if (p === 'CRITICAL' || p === 'HIGH') return '#dc2626'
  if (p === 'NORMAL' || p === 'MEDIUM') return '#2563eb'
  if (p === 'LOW') return '#16a34a'
  return '#475569'
})

const hasExistingRating = computed(() => Boolean(props.ticket?.rating && props.ticket?.feedback))

const ratingTextMap = {
  1: '1 - Poor',
  2: '2 - Fair',
  3: '3 - Good',
  4: '4 - Very Good',
  5: '5 - Excellent',
}
const ratingLabel = computed(() => ratingTextMap[rating.value] || `${rating.value} Stars`)

watch(
  () => props.ticket,
  (newVal) => {
    if (newVal) {
      rating.value = newVal.rating || 5
      feedback.value = newVal.feedback || ''
    }
  },
  { immediate: true },
)

function closeModal() {
  emit('update:modelValue', false)
}

function getAttIcon(att) {
  if (att.file_type === 'gdrive' || att.external_url) return 'add_link'
  const ext = (att.file_type || att.file_name || '').toLowerCase()
  if (ext.includes('pdf')) return 'picture_as_pdf'
  if (['png', 'jpg', 'jpeg'].some((x) => ext.includes(x))) return 'image'
  if (['doc', 'docx'].some((x) => ext.includes(x))) return 'description'
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
    $q.notify({ type: 'positive', message: 'Feedback submitted successfully.' })
    emit('refresh')
  } catch (err) {
    console.error('Failed to submit rating', err)
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Failed to submit rating.',
    })
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
      position: 'top-right',
    })
    emit('refresh')
    closeModal()
  } catch (err) {
    console.error('Failed to claim ticket', err)
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Failed to claim ticket.',
    })
  } finally {
    claiming.value = false
  }
}

async function resolveTicket() {
  if (!resolveResolution.value || !resolveResolution.value.trim()) return
  const id = props.ticket.real_id || props.ticket.id

  resolving.value = true
  try {
    const res = await api.post(`/tickets/${id}/resolve`, {
      remarks: resolveResolution.value.trim(),
      resolution: resolveResolution.value.trim(),
    })
    $q.notify({
      type: 'positive',
      message: res.data?.message || 'Ticket resolved successfully!',
    })
    showResolveDialog.value = false
    emit('refresh')
    closeModal()
  } catch (err) {
    console.error('Failed to resolve ticket', err)
    $q.notify({
      type: 'negative',
      message: err.response?.data?.message || 'Failed to resolve ticket.',
    })
  } finally {
    resolving.value = false
  }
}
</script>

<style lang="scss" scoped>
.user-view-modal {
  width: 820px !important;
  max-width: 95vw !important;

  &__icon-bg {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #e6f4ea;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

.line-height-tight {
  line-height: 1.2;
}

.form-label {
  font-size: 0.85rem;
}

.file-item {
  border: 1px solid #e5e7eb;
  transition: all 0.15s ease;

  &:hover {
    background: #f3f4f6;
    border-color: #cbd5e1;
  }
}

// ── Resolve Ticket Dialog Styling ─────────────────────────────
.resolve-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #e6f4ea;
  display: flex;
  align-items: center;
  justify-content: center;
}

.quick-picks-container {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  max-height: 140px;
  overflow-y: auto;

  /* Thin sleek scrollbar */
  &::-webkit-scrollbar {
    width: 4px;
  }
  &::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }
}

.quick-pick-btn {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 0.76rem;
  font-weight: 600;
  color: #374151;
  background: #ffffff;
  border: 1px solid #d1d5db;
  cursor: pointer;
  outline: none;
  transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);

  &:hover {
    background: #f0fdf4;
    color: #15803d;
    border-color: #86efac;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(22, 163, 74, 0.12);
  }

  &:active {
    transform: translateY(0);
  }

  &--active {
    background: #16a34a !important;
    color: #ffffff !important;
    border-color: #16a34a !important;
    box-shadow: 0 2px 6px rgba(22, 163, 74, 0.3) !important;
  }
}

.resolve-textarea {
  :deep(.q-field__native) {
    font-size: 0.88rem;
    line-height: 1.45;
  }
}
</style>
