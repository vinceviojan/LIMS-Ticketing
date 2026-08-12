<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" persistent>
    <q-card class="ticket-page__dialog" style="max-width: 650px; width: 100%;">
      <q-card-section class="ticket-page__dialog-head">
        <q-icon name="edit_note" size="26px" color="primary" />
        <span class="ticket-page__dialog-title">New Ticket</span>
        <q-space />
        <q-btn flat round dense icon="close" @click="closeModal" />
      </q-card-section>

      <q-separator />

      <q-form @submit="submitTicket">
        <q-card-section class="ticket-page__dialog-body">
          <!-- ── Division & Section (auto-filled, read-only) ────────────── -->
          <div class="ticket-page__form-row">
            <q-input :model-value="requesterDivision" label="Division" outlined dense readonly />
            <q-input :model-value="requesterSection" label="Section" outlined dense readonly />
          </div>

          <q-input
            v-model="form.title"
            label="Subject / Title *"
            outlined dense
            class="q-mt-sm q-mb-sm"
            :rules="[val => !!val || 'Required']"
          />

          <q-select
            v-model="form.category"
            :options="categoryOptions"
            label="Category *"
            outlined dense emit-value map-options
            class="q-mb-sm"
            :rules="[val => !!val || 'Category is required']"
          />

          <q-input
            v-model="form.description"
            label="Description *"
            outlined dense
            type="textarea"
            rows="3"
            class="q-mb-sm"
            :rules="[val => !!val || 'Required']"
          />

          <!-- ── Attachments Section ───────────────────────────────────── -->
          <div class="q-mt-md">
            <div class="text-subtitle2 text-weight-bold q-mb-xs">Attachments</div>
            <div class="text-caption text-grey-7 q-mb-sm">
              Acceptable file types: <strong>PDF, PNG, JPEG, DOC, DOCX</strong> or <strong>Google Drive links</strong>.
            </div>

            <!-- File Pickers -->
            <q-file
              v-model="fileList"
              label="Select / Drag Files to Attach"
              outlined dense multiple
              append
              use-chips
              accept=".pdf, .png, .jpg, .jpeg, .doc, .docx"
              @rejected="onFileRejected"
            >
              <template #prepend>
                <q-icon name="attach_file" />
              </template>
            </q-file>

            <!-- Attachment Items Preview & Loading Status -->
            <div v-if="fileList && fileList.length" class="q-mt-sm">
              <q-list bordered separator rounded class="bg-grey-1">
                <q-item v-for="(file, idx) in fileList" :key="file.name + idx" dense>
                  <q-item-section avatar>
                    <q-icon :name="getFileIcon(file.name)" color="primary" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-medium text-body2">{{ file.name }}</q-item-label>
                    <q-item-label caption>{{ formatBytes(file.size) }}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-btn flat round dense icon="cancel" color="negative" @click="removeFile(idx)" />
                  </q-item-section>
                </q-item>
              </q-list>
            </div>

            <!-- Google Drive / External Link Fields -->
            <div class="q-mt-md">
              <div class="row items-center justify-between q-mb-xs">
                <span class="text-caption text-weight-bold text-grey-8">Google Drive / External Links</span>
                <q-btn flat dense no-caps icon="add" label="Add Link" color="primary" size="sm" @click="addDriveLink" />
              </div>
              <div v-for="(link, lIdx) in form.gdrive_links" :key="lIdx" class="row items-center q-mb-xs gap-sm">
                <q-input
                  v-model="form.gdrive_links[lIdx]"
                  placeholder="https://drive.google.com/file/d/..."
                  outlined dense class="col"
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

        <q-card-actions align="right" class="ticket-page__dialog-actions">
          <q-btn flat no-caps label="Cancel" color="grey-7" @click="closeModal" />
          <q-btn
            unelevated no-caps type="submit"
            label="Submit Ticket"
            class="clay-btn clay-btn--primary"
            :loading="saving"
          />
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
  prefill: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue', 'refresh'])

const requesterDivision = computed(() => authStore?.user?.division?.name || authStore?.userDivision || '—')
const requesterSection = computed(() => authStore?.user?.section?.name || authStore?.userSection || '—')

const saving = ref(false)
const fileList = ref([])

function emptyForm() {
  return {
    title: props.prefill?.title || '',
    description: props.prefill?.description || '',
    category: props.prefill?.category || null,
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

<style lang="scss">
@import './TicketModal.scss';
</style>
