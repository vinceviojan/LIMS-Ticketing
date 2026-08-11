<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
    <q-card class="ticket-page__dialog">
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

        <div class="q-mt-md">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-btn
                v-if="ticket?.upload_intralab"
                flat color="primary" icon="attach_file" label="View Intralab Attachment"
                class="full-width" @click="viewAttachment('intralab')"
              />
              <div v-else class="ticket-page__no-attachment">No Intralab attachment</div>
            </div>
            <div class="col-12 col-md-6">
              <q-btn
                v-if="ticket?.upload_limsportal"
                flat color="primary" icon="attach_file" label="View LIMS Portal Attachment"
                class="full-width" @click="viewAttachment('limsportal')"
              />
              <div v-else class="ticket-page__no-attachment">No LIMS Portal attachment</div>
            </div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="ticket-page__dialog-actions">
        <q-btn flat no-caps label="Close" color="grey-7" @click="closeModal" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { computed } from 'vue'
import { api } from '../boot/axios'
import { useQuasar } from 'quasar'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  ticket:     { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue'])

const $q = useQuasar()

const statusLabels = {
  OPEN: 'Open',
  ESCALATED: 'Escalated',
  CLOSE: 'Closed',
  CANCEL: 'Canceled',
}

const statusLabel = computed(() => statusLabels[props.ticket?.status] || props.ticket?.status || '—')

function closeModal() {
  emit('update:modelValue', false)
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
</script>