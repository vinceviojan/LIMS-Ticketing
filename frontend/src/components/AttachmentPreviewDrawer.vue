<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    position="right"
    maximized
    transition-show="slide-left"
    transition-hide="slide-right"
  >
    <q-card class="attachment-drawer" style="width: 480px; max-width: 90vw; height: 100vh; display: flex; flex-direction: column;">
      <!-- Header -->
      <q-card-section class="bg-primary text-white row items-center justify-between q-py-sm">
        <div class="row items-center gap-xs">
          <q-icon :name="getIcon(attachment)" size="22px" />
          <span class="text-subtitle1 text-weight-bold ellipsis" style="max-width: 320px;">
            {{ attachment?.file_name || 'Attachment Preview' }}
          </span>
        </div>
        <q-btn flat round dense icon="close" color="white" v-close-popup />
      </q-card-section>

      <q-separator />

      <!-- Body Preview Area -->
      <q-card-section class="col flex flex-center bg-grey-2 q-pa-none overflow-hidden relative-position">
        <div v-if="loading" class="column items-center">
          <q-spinner-dots color="primary" size="40px" />
          <span class="text-caption text-grey-7 q-mt-xs">Loading attachment...</span>
        </div>

        <div v-else-if="error" class="column items-center q-pa-md text-center">
          <q-icon name="error_outline" color="negative" size="48px" />
          <span class="text-subtitle2 text-grey-8 q-mt-sm">{{ error }}</span>
          <q-btn unelevated no-caps label="Try Direct Download" color="primary" class="q-mt-sm" @click="downloadFile" />
        </div>

        <!-- Image Preview -->
        <img
          v-else-if="isImage"
          :src="blobUrl"
          alt="Attachment Preview"
          style="max-width: 100%; max-height: 100%; object-fit: contain; padding: 12px;"
        />

        <!-- PDF Preview -->
        <iframe
          v-else-if="isPdf"
          :src="blobUrl"
          style="width: 100%; height: 100%; border: none;"
        ></iframe>

        <!-- External Link / Fallback -->
        <div v-else class="column items-center q-pa-lg text-center">
          <q-icon name="insert_drive_file" color="primary" size="64px" />
          <div class="text-subtitle1 text-weight-bold q-mt-md">{{ attachment?.file_name }}</div>
          <div v-if="attachment?.file_size" class="text-caption text-grey-7">{{ formatBytes(attachment.file_size) }}</div>
          <div v-if="attachment?.external_url" class="text-caption text-primary text-weight-medium q-mt-xs word-break">
            {{ attachment.external_url }}
          </div>

          <q-btn
            v-if="attachment?.external_url"
            unelevated no-caps icon="open_in_new" label="Open Google Drive Link"
            color="primary" class="q-mt-md" @click="openExternal"
          />
          <q-btn
            v-else
            unelevated no-caps icon="download" label="Download File"
            color="primary" class="q-mt-md" @click="downloadFile"
          />
        </div>
      </q-card-section>

      <!-- Footer Controls -->
      <q-card-section class="bg-white row items-center justify-between q-py-sm">
        <div class="text-caption text-grey-7">
          <span v-if="attachment?.file_size">{{ formatBytes(attachment.file_size) }}</span>
          <span v-else-if="attachment?.file_type">{{ attachment.file_type.toUpperCase() }}</span>
        </div>

        <div class="row gap-xs">
          <q-btn
            v-if="blobUrl"
            flat dense icon="download" label="Save"
            color="primary" no-caps @click="downloadFile"
          />
          <q-btn flat dense label="Close" color="grey-7" no-caps v-close-popup />
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { api } from '../boot/axios'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  attachment: { type: Object, default: null },
})

defineEmits(['update:modelValue'])

const loading = ref(false)
const error   = ref(null)
const blobUrl = ref(null)

const isImage = computed(() => {
  if (!props.attachment) return false
  const type = (props.attachment.file_type || props.attachment.file_name || '').toLowerCase()
  return ['png', 'jpg', 'jpeg', 'gif', 'webp'].some(ext => type.includes(ext))
})

const isPdf = computed(() => {
  if (!props.attachment) return false
  const type = (props.attachment.file_type || props.attachment.file_name || '').toLowerCase()
  return type.includes('pdf')
})

watch(() => props.modelValue, async (isOpen) => {
  if (isOpen && props.attachment) {
    clearBlob()
    if (props.attachment.external_url) {
      return
    }
    await loadAttachmentBlob()
  } else {
    clearBlob()
  }
})

function clearBlob() {
  if (blobUrl.value) {
    URL.revokeObjectURL(blobUrl.value)
    blobUrl.value = null
  }
  error.value = null
  loading.value = false
}

async function loadAttachmentBlob() {
  if (!props.attachment?.id && !props.attachment?.legacy_type) return
  loading.value = true
  error.value = null
  try {
    const url = props.attachment.legacy_type
      ? `/tickets/${props.attachment.ticket_id}/attachment/${props.attachment.legacy_type}`
      : `/attachments/${props.attachment.id}`;
      
    const res = await api.get(url, {
      responseType: 'blob'
    })
    blobUrl.value = URL.createObjectURL(res.data)
  } catch (err) {
    console.error('Failed to load attachment blob', err)
    error.value = 'Unable to preview file.'
  } finally {
    loading.value = false
  }
}

function openExternal() {
  if (props.attachment?.external_url) {
    window.open(props.attachment.external_url, '_blank')
  }
}

function downloadFile() {
  if (props.attachment?.external_url) {
    openExternal()
    return
  }

  if (blobUrl.value) {
    const a = document.createElement('a')
    a.href = blobUrl.value
    a.download = props.attachment?.file_name || 'attachment'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
  } else if (props.attachment?.legacy_type && props.attachment?.ticket_id) {
    window.open(`/api/tickets/${props.attachment.ticket_id}/attachment/${props.attachment.legacy_type}`, '_blank')
  } else if (props.attachment?.id) {
    window.open(`/api/attachments/${props.attachment.id}`, '_blank')
  }
}

function getIcon(att) {
  if (!att) return 'attach_file'
  if (att.external_url || att.file_type === 'gdrive') return 'add_link'
  const ext = (att.file_type || att.file_name || '').toLowerCase()
  if (ext.includes('pdf')) return 'picture_as_pdf'
  if (['png', 'jpg', 'jpeg'].some(x => ext.includes(x))) return 'image'
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
</script>

<style scoped>
.word-break {
  word-break: break-all;
}
</style>
