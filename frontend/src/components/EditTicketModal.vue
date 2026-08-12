<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    persistent
    transition-show="scale"
    transition-hide="scale"
  >
    <q-card class="edit-ticket-modal" style="width: 820px; max-width: 95vw; border-radius: 16px;">

      <!-- ── Header ─────────────────────────────────────────────── -->
      <q-card-section class="edit-ticket-modal__head bg-white q-pa-md row items-center justify-between">
        <div class="row items-center gap-sm">
          <div class="edit-ticket-modal__icon-bg">
            <q-icon name="edit_note" size="22px" color="primary" />
          </div>
          <div>
            <div class="text-subtitle1 text-weight-bold text-grey-10 line-height-tight">Edit Ticket</div>
            <div class="text-caption text-grey-6">Update ticket information and assignments</div>
          </div>
        </div>
        <q-btn flat round dense icon="close" color="grey-6" @click="closeModal" />
      </q-card-section>

      <q-separator />

      <!-- ── Body ────────────────────────────────────────────────── -->
      <q-form @submit="submitTicket">
        <q-card-section class="q-pa-lg" style="max-height: 68vh; overflow-y: auto;">

          <!-- 1st Row: Ticket # (read-only) -->
          <div class="q-mb-md">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Ticket #</label>
            <q-input
              :model-value="ticket?.ticket_no || (ticket?.id ? '#' + ticket.id : '—')"
              outlined dense readonly
              bg-color="grey-1"
              input-class="text-weight-bold text-grey-9"
            >
              <template #prepend>
                <q-icon name="tag" color="primary" size="20px" />
              </template>
            </q-input>
          </div>

          <!-- 2nd Row: Status & Priority -->
          <div class="row q-col-gutter-md q-mb-md">
            <!-- Status -->
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Status</label>
              <q-select
                v-model="form.status"
                :options="statusOptions"
                outlined dense emit-value map-options
                :disable="!isAdmin"
                bg-color="white"
              >
                <template #prepend>
                  <q-icon name="info" :style="{ color: statusColor }" size="20px" />
                </template>
                <template #selected>
                  <span :style="{ color: statusColor, fontWeight: '700' }">
                    {{ statusOptions.find(s => s.value === form.status)?.label || form.status }}
                  </span>
                </template>
                <template #option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section>
                      <q-item-label :style="{ color: getStatusHex(scope.opt.value), fontWeight: '600' }">
                        {{ scope.opt.label }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>

            <!-- Priority -->
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Priority</label>
              <q-select
                v-model="form.priority"
                :options="priorityOptions"
                outlined dense emit-value map-options
                :disable="!isAdmin"
                bg-color="white"
              >
                <template #prepend>
                  <q-icon name="flag" :style="{ color: priorityColor }" size="20px" />
                </template>
                <template #selected>
                  <span :style="{ color: priorityColor, fontWeight: '700' }">
                    {{ priorityOptions.find(p => p.value === form.priority)?.label || form.priority }}
                  </span>
                </template>
                <template #option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section>
                      <q-item-label :style="{ color: getPriorityHex(scope.opt.value), fontWeight: '600' }">
                        {{ scope.opt.label }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>
          </div>

          <!-- 3rd Row: Subject / Title & Category -->
          <div class="row q-col-gutter-md q-mb-md">
            <!-- Subject / Title -->
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Subject / Title <span class="text-negative">*</span></label>
              <q-input
                v-model="form.title"
                outlined dense
                placeholder="Enter ticket subject..."
                :rules="[val => !!val || 'Subject is required']"
                bg-color="white"
              >
                <template #prepend>
                  <q-icon name="subtitles" color="grey-5" size="20px" />
                </template>
              </q-input>
            </div>

            <!-- Category -->
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Category</label>
              <q-select
                v-model="form.category"
                :options="categoryOptions"
                outlined dense emit-value map-options
                clearable
                placeholder="Select category"
                bg-color="white"
              >
                <template #prepend>
                  <q-icon name="category" color="grey-5" size="20px" />
                </template>
              </q-select>
            </div>
          </div>

          <!-- 4th Row: Assign Staff (admin only) -->
          <div v-if="isAdmin" class="q-mb-md">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Assigned Staff</label>
            <q-select
              v-model="form.assigned_staff_id"
              :options="staffOptions"
              outlined dense emit-value map-options
              clearable
              placeholder="Select staff member"
              bg-color="white"
            >
              <template #prepend>
                <q-icon name="person_pin" color="grey-5" size="20px" />
              </template>
            </q-select>
          </div>

          <!-- 5th Row: Description -->
          <div class="q-mb-md">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Description <span class="text-negative">*</span></label>
            <q-input
              v-model="form.description"
              outlined dense
              type="textarea" rows="4"
              placeholder="Describe the issue in detail..."
              :rules="[val => !!val || 'Description is required']"
              bg-color="white"
            >
              <template #prepend>
                <q-icon name="notes" color="grey-5" size="20px" />
              </template>
            </q-input>
          </div>

          <!-- ── Existing Attachments ─────────────────────────────── -->
          <div class="q-mt-lg">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-sm">Existing Attachments</label>

            <div v-if="ticket?.attachments && ticket.attachments.length" class="row q-col-gutter-sm q-mb-md">
              <div
                v-for="att in ticket.attachments"
                :key="att.id"
                class="col-12 col-sm-6"
              >
                <div
                  class="file-item row items-center justify-between q-pa-sm bg-grey-2 rounded-borders cursor-pointer"
                  @click="openAttachment(att)"
                >
                  <div class="row items-center gap-sm col ellipsis">
                    <q-avatar size="32px" color="primary" text-color="white" :icon="getAttIcon(att)" />
                    <div class="col ellipsis">
                      <div class="text-weight-medium text-body2 ellipsis">{{ att.file_name }}</div>
                      <div class="text-caption text-grey-7" v-if="att.file_size">{{ formatBytes(att.file_size) }}</div>
                      <div class="text-caption text-primary ellipsis" v-else-if="att.external_url">{{ att.external_url }}</div>
                    </div>
                  </div>
                  <q-btn flat round dense icon="open_in_new" color="primary" size="sm" />
                </div>
              </div>
            </div>
            <div v-else class="text-grey-6 text-caption italic q-py-xs q-mb-md">
              No existing attachments.
            </div>

            <!-- ── Add Additional Attachments ──────────────────────── -->
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Add Attachments</label>
            <div class="text-caption text-grey-6 q-mb-sm">
              Acceptable: <strong>PDF, PNG, JPEG, DOC, DOCX</strong> or Google Drive links.
            </div>

            <q-file
              v-model="fileList"
              outlined dense multiple append use-chips
              accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
              bg-color="white"
              class="q-mb-md"
              @rejected="onFileRejected"
            >
              <template #prepend>
                <q-icon name="attach_file" color="grey-6" />
              </template>
              <template #label>
                <span class="text-grey-6">Select / Drag Files to Attach</span>
              </template>
            </q-file>

            <!-- New File Preview -->
            <div v-if="fileList && fileList.length" class="q-mb-md">
              <div class="row q-col-gutter-sm">
                <div v-for="(file, idx) in fileList" :key="file.name + idx" class="col-12 col-sm-6">
                  <div class="file-item row items-center justify-between q-pa-sm bg-grey-2 rounded-borders">
                    <div class="row items-center gap-sm col ellipsis">
                      <q-avatar size="32px" color="primary" text-color="white" :icon="getFileIcon(file.name)" />
                      <div class="col ellipsis">
                        <div class="text-weight-medium text-body2 ellipsis">{{ file.name }}</div>
                        <div class="text-caption text-grey-7">{{ formatBytes(file.size) }}</div>
                      </div>
                    </div>
                    <q-btn flat round dense icon="cancel" color="negative" size="sm" @click="removeFile(idx)" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Google Drive Links -->
            <div class="q-mt-sm">
              <div class="row items-center justify-between q-mb-sm">
                <span class="text-caption text-weight-bold text-grey-8">Google Drive / External Links</span>
                <q-btn flat dense no-caps icon="add" label="Add Link" color="primary" size="sm" @click="addDriveLink" />
              </div>
              <div v-for="(link, lIdx) in form.gdrive_links" :key="lIdx" class="row items-center q-mb-xs gap-sm">
                <q-input
                  v-model="form.gdrive_links[lIdx]"
                  placeholder="https://drive.google.com/file/d/..."
                  outlined dense
                  class="col"
                  bg-color="white"
                >
                  <template #prepend>
                    <q-icon name="add_link" color="primary" />
                  </template>
                </q-input>
                <q-btn flat round dense icon="delete" color="negative" @click="removeDriveLink(lIdx)" />
              </div>
            </div>
          </div>

        </q-card-section>

        <q-separator />

        <!-- ── Footer ──────────────────────────────────────────────── -->
        <q-card-actions align="right" class="q-pa-md bg-grey-1 gap-sm">
          <q-btn flat no-caps label="Cancel" color="grey-7" @click="closeModal" />
          <q-btn
            unelevated no-caps type="submit"
            color="primary"
            icon="save"
            label="Save Changes"
            class="text-weight-medium"
            :loading="saving"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- Right Side Drawer Attachment Preview -->
  <AttachmentPreviewDrawer
    v-model="showDrawer"
    :attachment="selectedAttachment"
  />
</template>

<script setup>
import { ref, watch, computed, inject } from 'vue'
import { api } from '../boot/axios'
import { useQuasar } from 'quasar'
import AttachmentPreviewDrawer from './AttachmentPreviewDrawer.vue'

const props = defineProps({
  modelValue:      { type: Boolean, default: false },
  ticket:          { type: Object,  default: () => ({}) },
  categoryOptions: { type: Array,   default: () => [] },
  staffOptions:    { type: Array,   default: () => [] },
  priorityOptions: { type: Array,   default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'update:mode', 'refresh'])

const $q = useQuasar()
const authStore = inject('authStore')

const isAdmin  = computed(() => authStore.userRole === 'admin')
const saving   = ref(false)
const fileList = ref([])
const showDrawer = ref(false)
const selectedAttachment = ref(null)

const statusOptions = [
  { label: 'Open',      value: 'OPEN' },
  { label: 'On-going',  value: 'ON-GOING' },
  { label: 'Escalated', value: 'ESCALATED' },
  { label: 'Resolved',  value: 'RESOLVED' },
  { label: 'Closed',    value: 'CLOSE' },
  { label: 'Canceled',  value: 'CANCEL' },
]

// ── Color Helpers ────────────────────────────────────────────────
function getStatusHex(status) {
  const map = {
    'OPEN':     '#c2410c',
    'ON-GOING': '#1d4ed8',
    'PENDING':  '#ca8a04',
    'ESCALATED':'#9333ea',
    'RESOLVED': '#16a34a',
    'CLOSE':    '#475569',
    'CANCEL':   '#dc2626',
  }
  return map[(status || '').toUpperCase()] || '#475569'
}

function getPriorityHex(priority) {
  const map = {
    'HIGH':     '#dc2626',
    'CRITICAL': '#7c3aed',
    'NORMAL':   '#2563eb',
    'MEDIUM':   '#2563eb',
    'LOW':      '#16a34a',
  }
  return map[(priority || '').toUpperCase()] || '#475569'
}

const statusColor   = computed(() => getStatusHex(form.value.status))
const priorityColor = computed(() => getPriorityHex(form.value.priority))

function emptyForm() {
  return {
    title: '',
    description: '',
    priority: 'NORMAL',
    status: 'OPEN',
    category: null,
    assigned_staff_id: null,
    gdrive_links: [],
  }
}

const form = ref(emptyForm())

watch(() => props.modelValue, (isOpen) => {
  if (isOpen && props.ticket) {
    form.value = {
      title:             props.ticket.title || props.ticket.issue || '',
      description:       props.ticket.description || '',
      priority:          props.ticket.priority || props.ticket.urgency || 'NORMAL',
      status:            props.ticket.status || 'OPEN',
      category:          props.ticket.problem_category_id || null,
      assigned_staff_id: props.ticket.assigned_staff_id || null,
      gdrive_links:      [],
    }
    fileList.value = []
  } else if (!isOpen) {
    form.value = emptyForm()
    fileList.value = []
  }
})

function closeModal() {
  emit('update:modelValue', false)
  emit('update:mode', 'view')
}

function removeFile(index)      { fileList.value.splice(index, 1) }
function addDriveLink()         { form.value.gdrive_links.push('') }
function removeDriveLink(index) { form.value.gdrive_links.splice(index, 1) }

function onFileRejected(rejectedEntries) {
  $q.notify({
    type: 'warning',
    message: `${rejectedEntries.length} file(s) rejected. Only PDF, PNG, JPEG, DOC, DOCX are allowed.`,
  })
}

function getAttIcon(att) {
  if (att.file_type === 'gdrive' || att.external_url) return 'add_link'
  const ext = (att.file_type || att.file_name || '').toLowerCase()
  if (ext.includes('pdf')) return 'picture_as_pdf'
  if (['png', 'jpg', 'jpeg'].some(x => ext.includes(x))) return 'image'
  if (['doc', 'docx'].some(x => ext.includes(x))) return 'description'
  return 'insert_drive_file'
}

function getFileIcon(name = '') {
  const ext = name.split('.').pop().toLowerCase()
  if (ext === 'pdf') return 'picture_as_pdf'
  if (['png', 'jpg', 'jpeg'].includes(ext)) return 'image'
  if (['doc', 'docx'].includes(ext)) return 'description'
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

async function submitTicket() {
  saving.value = true
  try {
    const payload = new FormData()
    payload.append('_method', 'PUT')
    payload.append('issue',       form.value.title)
    payload.append('description', form.value.description || '')

    if (form.value.category) payload.append('problem_category_id', form.value.category)

    if (isAdmin.value) {
      payload.append('urgency', form.value.priority)
      if (form.value.status) payload.append('status', form.value.status)
      if (form.value.assigned_staff_id) payload.append('assigned_staff_id', form.value.assigned_staff_id)
    }

    if (fileList.value && fileList.value.length) {
      fileList.value.forEach(f => payload.append('attachments[]', f))
    }

    if (form.value.gdrive_links && form.value.gdrive_links.length) {
      form.value.gdrive_links.forEach(link => {
        if (link && link.trim()) payload.append('gdrive_links[]', link.trim())
      })
    }

    const id = props.ticket?.real_id || props.ticket?.id
    await api.post(`/tickets/${id}`, payload, { headers: { 'Content-Type': 'multipart/form-data' } })

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

<style lang="scss" scoped>
.edit-ticket-modal {
  width: 820px !important;
  max-width: 95vw !important;

  &__icon-bg {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #e8f0fe;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__head {
    border-bottom: 1px solid #f1f5f9;
  }
}

.line-height-tight { line-height: 1.2; }

.form-label {
  font-size: 0.85rem;
  display: block;
}

.file-item {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  transition: all 0.15s ease;

  &:hover {
    background: #f3f4f6;
    border-color: #cbd5e1;
  }
}

.gap-sm { gap: 8px; }
.gap-xs { gap: 4px; }
</style>
