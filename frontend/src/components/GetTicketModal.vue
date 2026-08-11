<template>
  <q-dialog :model-value="props.modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card style="width: 700px; max-width: 90vw">
      <!-- Header -->
      <q-card-section class="row items-center">
        <div class="text-h6">View Ticket</div>

        <q-space />

        <q-btn icon="close" flat round dense @click="closeModal" />
      </q-card-section>

      <q-separator />

      <!-- Ticket Information -->
      <q-card-section v-if="props.ticket">
        <q-input
          label="Ticket ID"
          :model-value="props.ticket.id"
          readonly
          outlined
          class="q-mb-md"
        />

        <q-input
          label="Title"
          :model-value="props.ticket.issue"
          readonly
          outlined
          class="q-mb-md"
        />

        <q-input
          label="Requester"
          :model-value="props.ticket.user.name"
          readonly
          outlined
          class="q-mb-md"
        />

        <q-input
          label="Category"
          :model-value="props.ticket.problem_category.categories"
          readonly
          outlined
          class="q-mb-md"
        />

        <!-- Editable Priority -->
        <q-select
          label="Priority"
          :model-value="props.ticket.urgency"
          :options="props.priorityOptions"
          outlined
          class="q-mb-md"
        />

        <q-input
          label="Status"
          :model-value="props.ticket.status"
          readonly
          outlined
          class="q-mb-md"
        />
      </q-card-section>

      <q-card-section v-else>
        <div class="text-grey">No ticket selected.</div>
      </q-card-section>

      <q-separator />

      <!-- Actions -->
      <q-card-actions align="right">
        <q-btn flat label="Cancel" @click="closeModal" />

        <q-btn color="primary" label="Get Ticket" @click="emit('save')" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  ticket: {
    type: Object,
    default: null,
  },
  priorityOptions: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update:modelValue', 'save'])

function closeModal() {
  emit('update:modelValue', false)
}
</script>
