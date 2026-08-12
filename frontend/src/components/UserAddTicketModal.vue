<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" persistent transition-show="scale" transition-hide="scale">
    <q-card class="ticket-page__dialog user-add-modal" style="width: 780px; max-width: 92vw; border-radius: 16px; overflow: hidden;">
      
      <!-- ── Header ─────────────────────────────────────────────── -->
      <q-card-section class="user-add-modal__head bg-white q-pa-md row items-center justify-between border-bottom">
        <div class="row items-center gap-sm">
          <div class="user-add-modal__icon-bg">
            <q-icon name="confirmation_number" size="22px" color="primary" />
          </div>
          <div>
            <div class="text-subtitle1 text-weight-bold text-grey-10 line-height-tight">Submit New Ticket</div>
            <div class="text-caption text-grey-6">Fill in the details below to request assistance</div>
          </div>
        </div>
        <q-btn flat round dense icon="close" color="grey-6" @click="closeModal" />
      </q-card-section>

      <q-separator />

      <!-- ── Form ────────────────────────────────────────────────── -->
      <q-form @submit="submitTicket">
        <q-card-section class="user-add-modal__body q-pa-lg">
          
          <!-- Two Column Row: Subject & Category -->
          <div class="row q-col-gutter-md q-mb-xs">
            <!-- Subject / Title -->
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">
                Subject / Title <span class="text-negative">*</span>
              </label>
              <q-input
                v-model="form.title"
                placeholder="e.g., Cannot access server / Equipment reservation request"
                outlined dense
                bg-color="grey-1"
                :rules="[val => !!val || 'Please enter a title for your ticket']"
              >
                <template #prepend>
                  <q-icon name="subtitles" color="primary" size="20px" />
                </template>
              </q-input>
            </div>

            <!-- Category Select -->
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">
                Category <span class="text-negative">*</span>
              </label>
              <q-select
                v-model="form.category"
                :options="categoryOptions"
                label="Select a category"
                outlined dense emit-value map-options
                bg-color="grey-1"
                :rules="[val => !!val || 'Please select a problem category']"
              >
                <template #prepend>
                  <q-icon name="category" color="primary" size="20px" />
                </template>
              </q-select>
            </div>
          </div>

          <!-- Description Textarea -->
          <div class="q-mb-md">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">
              Description <span class="text-negative">*</span>
            </label>
            <q-input
              v-model="form.description"
              placeholder="Provide details about your issue, error message, or scheduling request..."
              outlined dense
              type="textarea"
              rows="4"
              bg-color="grey-1"
              :rules="[val => !!val || 'Please describe your concern']"
            >
              <template #prepend>
                <q-icon name="notes" color="primary" size="20px" />
              </template>
            </q-input>
          </div>

          <!-- Target Resolution Date -->
          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">
                Target Resolution Date <span class="text-caption text-grey-5">(Optional)</span>
              </label>
              <q-input
                v-model="form.target_resolution_date"
                outlined dense type="date"
                bg-color="grey-1"
                placeholder="Select target date"
              >
                <template #prepend>
                  <q-icon name="event" color="primary" size="20px" />
                </template>
              </q-input>
            </div>
          </div>

          <!-- Attachments Dropzone -->
          <div class="q-mt-lg">
            <div class="row items-center justify-between q-mb-xs">
              <label class="form-label text-weight-bold text-grey-8">File Attachments</label>
              <span class="text-caption text-grey-6">PDF, PNG, JPG, DOC, DOCX</span>
            </div>

            <!-- Custom Styled File Select -->
            <q-file
              v-model="fileList"
              outlined dense multiple
              append
              use-chips
              accept=".pdf, .png, .jpg, .jpeg, .doc, .docx"
              bg-color="grey-1"
              class="attachment-picker"
              @rejected="onFileRejected"
            >
              <template #prepend>
                <q-icon name="cloud_upload" color="primary" size="20px" />
              </template>
              <template #hint>
                Click or drag files here to attach (Max 10MB per file)
              </template>
            </q-file>

            <!-- Uploaded Files Preview Cards -->
            <div v-if="fileList && fileList.length" class="q-mt-sm file-list-container">
              <div
                v-for="(file, idx) in fileList"
                :key="file.name + idx"
                class="file-item row items-center justify-between q-pa-sm q-mb-xs bg-grey-2 rounded-borders"
              >
                <div class="row items-center gap-sm col">
                  <q-avatar size="32px" color="primary" text-color="white" :icon="getFileIcon(file.name)" />
                  <div class="col ellipsis">
                    <div class="text-weight-medium text-body2 ellipsis">{{ file.name }}</div>
                    <div class="text-caption text-grey-7">{{ formatBytes(file.size) }}</div>
                  </div>
                </div>
                <q-btn flat round dense icon="close" color="grey-7" size="sm" @click="removeFile(idx)" />
              </div>
            </div>
          </div>

          <!-- Google Drive / External Links Section -->
          <div class="q-mt-lg">
            <div class="row items-center justify-between q-mb-xs">
              <label class="form-label text-weight-bold text-grey-8">Google Drive / External Links</label>
              <q-btn
                flat dense no-caps
                icon="add_link"
                label="Add Link"
                color="primary"
                size="sm"
                class="text-weight-bold"
                @click="addDriveLink"
              />
            </div>
            
            <div v-if="!form.gdrive_links.length" class="text-caption text-grey-5 italic q-mb-xs">
              No external links added. Click "Add Link" to include Google Drive or cloud storage URLs.
            </div>

            <div
              v-for="(link, lIdx) in form.gdrive_links"
              :key="lIdx"
              class="row items-center q-mb-xs gap-sm"
            >
              <q-input
                v-model="form.gdrive_links[lIdx]"
                placeholder="https://drive.google.com/file/d/..."
                outlined dense
                bg-color="grey-1"
                class="col"
              >
                <template #prepend>
                  <q-icon name="link" color="primary" size="20px" />
                </template>
              </q-input>
              <q-btn flat round dense icon="delete_outline" color="negative" size="md" @click="removeDriveLink(lIdx)" />
            </div>
          </div>

        </q-card-section>

        <q-separator />

        <!-- ── Modal Actions Footer ───────────────────────────── -->
        <q-card-actions align="right" class="q-pa-md bg-grey-1 gap-sm">
          <q-btn
            flat no-caps
            label="Cancel"
            color="grey-8"
            class="text-weight-medium"
            @click="closeModal"
          />
          <q-btn
            unelevated no-caps
            type="submit"
            label="Submit Ticket"
            icon-right="send"
            color="primary"
            class="submit-btn text-weight-bold"
            :loading="saving"
          />
        </q-card-actions>

      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { api } from '../boot/axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  categoryOptions: { type: Array, default: () => [] },
  prefill: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue', 'refresh'])

const saving = ref(false)
const fileList = ref([])

function emptyForm() {
  return {
    title: props.prefill?.title || '',
    description: props.prefill?.description || '',
    category: props.prefill?.category || null,
    target_resolution_date: null,
    gdrive_links: [],
  }
}

const form = ref(emptyForm())

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    form.value = {
      title: props.prefill?.title || '',
      description: props.prefill?.description || '',
      category: props.prefill?.category || null,
      target_resolution_date: null,
      gdrive_links: [],
    }
    fileList.value = []
  }
})

function closeModal() {
  emit('update:modelValue', false)
}

function removeFile(index) {
  fileList.value.splice(index, 1)
}

function addDriveLink() {
  form.value.gdrive_links.push('')
}

function removeDriveLink(index) {
  form.value.gdrive_links.splice(index, 1)
}

function onFileRejected(rejectedEntries) {
  $q.notify({
    type: 'warning',
    message: `${rejectedEntries.length} file(s) were rejected. Only PDF, PNG, JPEG, DOC, and DOCX files are allowed.`,
  })
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

async function submitTicket() {
  saving.value = true
  try {
    const payload = new FormData()
    payload.append('issue', form.value.title)
    payload.append('description', form.value.description || '')
    if (form.value.category) payload.append('problem_category_id', form.value.category)
    if (form.value.target_resolution_date) payload.append('target_resolution_date', form.value.target_resolution_date)

    // Append multiple files
    if (fileList.value && fileList.value.length) {
      fileList.value.forEach((f) => {
        payload.append('attachments[]', f)
      })
    }

    // Append Google Drive links
    if (form.value.gdrive_links && form.value.gdrive_links.length) {
      form.value.gdrive_links.forEach((link) => {
        if (link && link.trim()) {
          payload.append('gdrive_links[]', link.trim())
        }
      })
    }

    await api.post('/tickets', payload, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

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

    $q.notify({
      type: 'negative',
      message: msg + (errs ? ' ' + errs : ''),
      position: 'top',
      timeout: 5000,
      actions: [{ label: 'Dismiss', color: 'white' }]
    })
  } finally {
    saving.value = false
  }
}
</script>

<style lang="scss" scoped>
.user-add-modal {
  width: 780px !important;
  max-width: 92vw !important;

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
  }
}

.submit-btn {
  border-radius: 8px;
  padding: 6px 20px;
}
</style>
