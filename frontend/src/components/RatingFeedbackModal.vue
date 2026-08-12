<template>
  <q-dialog
    :model-value="modelValue"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <q-card style="width: 520px; max-width: 92vw; border-radius: 16px;" class="bg-white shadow-10">
      
      <!-- Header -->
      <q-card-section class="q-pa-lg bg-amber-1 border-bottom-amber row items-center justify-between">
        <div class="row items-center gap-sm">
          <q-avatar size="42px" color="amber-3" text-color="amber-10" icon="rate_review" />
          <div>
            <div class="text-h6 text-weight-bolder text-amber-10 line-height-tight">
              Service Rating & Feedback
            </div>
            <div class="text-caption text-grey-8">
              Share your experience for Ticket {{ ticket?.ticket_no || '#' + ticket?.id }}
            </div>
          </div>
        </div>
        <q-btn flat round dense icon="close" color="grey-7" v-close-popup />
      </q-card-section>

      <!-- Ticket Overview Card -->
      <q-card-section class="q-px-lg q-pt-md q-pb-none">
        <div class="q-pa-md bg-grey-1 rounded-borders border-grey">
          <div class="text-subtitle2 text-weight-bold text-dark ellipsis q-mb-xs">
            {{ ticket?.title || 'Untitled Ticket' }}
          </div>
          <div class="row items-center text-caption text-grey-7 gap-sm wrap">
            <div><strong>Category:</strong> {{ ticket?.category || 'General' }}</div>
            <div>•</div>
            <div><strong>Resolved By:</strong> {{ ticket?.assignedStaff || 'Support Staff' }}</div>
          </div>
        </div>
      </q-card-section>

      <!-- Rating & Feedback Form -->
      <q-card-section class="q-pa-lg">
        <div class="q-mb-md">
          <div class="text-subtitle2 text-weight-bold text-dark q-mb-xs">
            How would you rate the resolution service? <span class="text-negative">*</span>
          </div>
          <div class="row items-center gap-md q-py-xs">
            <q-rating
              v-model="rating"
              max="5"
              size="34px"
              color="amber-8"
              icon="star"
            />
            <q-chip dense color="amber-2" text-color="amber-10" class="text-weight-bold" style="font-size: 0.85rem; padding: 4px 12px;">
              {{ ratingLabel }}
            </q-chip>
          </div>
        </div>

        <div class="q-mb-sm">
          <div class="text-subtitle2 text-weight-bold text-dark q-mb-xs">
            Your Comments & Feedback <span class="text-negative">*</span>
          </div>
          <q-input
            v-model="feedback"
            outlined
            dense
            type="textarea"
            rows="3"
            placeholder="Tell us about the service quality, speed, or any suggestions..."
            bg-color="white"
          />
        </div>
      </q-card-section>

      <!-- Actions -->
      <q-separator />
      <q-card-actions align="right" class="q-pa-md bg-grey-1 gap-sm">
        <q-btn
          flat
          no-caps
          label="Cancel"
          color="grey-7"
          v-close-popup
        />
        <q-btn
          color="amber-9"
          label="Submit Feedback"
          icon="send"
          unelevated
          no-caps
          class="text-weight-bold"
          style="padding: 6px 20px;"
          :loading="submitting"
          :disable="!rating || !feedback.trim()"
          @click="submitRating"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../boot/axios'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  ticket: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['update:modelValue', 'submitted'])

const $q = useQuasar()
const rating = ref(5)
const feedback = ref('')
const submitting = ref(false)

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

async function submitRating() {
  if (!props.ticket?.id || !rating.value || !feedback.value.trim()) return

  submitting.value = true
  try {
    await api.post(`/tickets/${props.ticket.id}/rating`, {
      rating: rating.value,
      feedback: feedback.value.trim(),
    })

    $q.notify({
      type: 'positive',
      message: 'Thank you! Your rating and feedback have been submitted.',
      icon: 'thumb_up',
    })

    emit('update:modelValue', false)
    emit('submitted')
  } catch (error) {
    console.error('Failed to submit rating', error)
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to submit rating. Please try again.',
    })
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.border-bottom-amber {
  border-bottom: 1px solid #fde68a;
}
.border-grey {
  border: 1px solid #e2e8f0;
}
.line-height-tight {
  line-height: 1.2;
}
.gap-sm {
  gap: 8px;
}
.gap-md {
  gap: 16px;
}
</style>
