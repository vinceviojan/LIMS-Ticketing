<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    persistent
    transition-show="scale"
    transition-hide="scale"
  >
    <q-card class="view-user-modal" style="width: 820px; max-width: 95vw; border-radius: 16px;">

      <!-- ── Header ─────────────────────────────────────────────── -->
      <q-card-section class="bg-white q-pa-md row items-center justify-between border-bottom">
        <div class="row items-center gap-sm">
          <div class="icon-bg">
            <q-icon name="manage_accounts" size="22px" color="primary" />
          </div>
          <div>
            <div class="text-subtitle1 text-weight-bold text-grey-10 line-height-tight">User Account Details</div>
            <div class="text-caption text-grey-6">Detailed user profile and system access permissions</div>
          </div>
        </div>
        <q-btn flat round dense icon="close" color="grey-6" @click="closeModal" />
      </q-card-section>

      <q-separator />

      <!-- ── Body ────────────────────────────────────────────────── -->
      <q-card-section class="q-pa-lg" style="max-height: 65vh; overflow-y: auto;">

        <!-- 1st Row: Full Name & Email -->
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Full Name</label>
            <q-input
              :model-value="fullName"
              outlined dense readonly
              bg-color="grey-1"
              input-class="text-weight-bold text-grey-9"
            >
              <template #prepend>
                <q-icon name="person" color="primary" size="20px" />
              </template>
            </q-input>
          </div>

          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Email Address</label>
            <q-input
              :model-value="user?.email || '—'"
              outlined dense readonly
              bg-color="grey-1"
              input-class="text-weight-bold text-grey-9"
            >
              <template #prepend>
                <q-icon name="email" color="primary" size="20px" />
              </template>
            </q-input>
          </div>
        </div>

        <!-- 2nd Row: Role & Status -->
        <div class="row q-col-gutter-md q-mb-md">
          <!-- Role -->
          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Role</label>
            <q-input
              :model-value="user?.role || '—'"
              outlined dense readonly
              bg-color="grey-1"
              :input-style="{ color: roleColor, fontWeight: '700' }"
            >
              <template #prepend>
                <q-icon :name="roleIcon" :style="{ color: roleColor }" size="20px" />
              </template>
            </q-input>
          </div>

          <!-- Status -->
          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Account Status</label>
            <q-input
              :model-value="user?.status || '—'"
              outlined dense readonly
              bg-color="grey-1"
              :input-style="{ color: statusColor, fontWeight: '700' }"
            >
              <template #prepend>
                <q-icon name="toggle_on" :style="{ color: statusColor }" size="20px" />
              </template>
            </q-input>
          </div>
        </div>

        <!-- 3rd Row: Division & Section -->
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Division</label>
            <q-input
              :model-value="user?.division?.name || 'Laboratory Services Division'"
              outlined dense readonly
              bg-color="grey-1"
              input-class="text-weight-medium text-grey-9"
            >
              <template #prepend>
                <q-icon name="domain" color="primary" size="20px" />
              </template>
            </q-input>
          </div>

          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Section</label>
            <q-input
              :model-value="user?.section?.name || 'Unassigned'"
              outlined dense readonly
              bg-color="grey-1"
              input-class="text-weight-medium text-grey-9"
            >
              <template #prepend>
                <q-icon name="apartment" color="primary" size="20px" />
              </template>
            </q-input>
          </div>
        </div>

        <!-- 4th Row: Position & User ID -->
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Position</label>
            <q-input
              :model-value="user?.position || '—'"
              outlined dense readonly
              bg-color="grey-1"
              input-class="text-weight-medium text-grey-9"
            >
              <template #prepend>
                <q-icon name="work_outline" color="primary" size="20px" />
              </template>
            </q-input>
          </div>

          <div class="col-12 col-sm-6">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Tickets Created</label>
            <q-input
              :model-value="ticketsCreatedDisplay"
              outlined dense readonly
              bg-color="grey-1"
              input-class="text-weight-bold text-grey-9"
            >
              <template #prepend>
                <q-icon name="confirmation_number" color="primary" size="20px" />
              </template>
            </q-input>
          </div>
        </div>

      </q-card-section>

      <q-separator />

      <!-- ── Footer ──────────────────────────────────────────────── -->
      <q-card-actions align="right" class="q-pa-md bg-grey-1 gap-sm">
        <q-btn flat no-caps label="Close" color="grey-7" @click="closeModal" />
        <q-btn
          unelevated no-caps
          color="primary"
          icon="edit"
          label="Edit User"
          class="text-weight-medium"
          @click="onEditClick"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  user:       { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue', 'edit'])

function closeModal() {
  emit('update:modelValue', false)
}

function onEditClick() {
  closeModal()
  emit('edit', props.user)
}

const fullName = computed(() => {
  if (!props.user) return '—'
  return `${props.user.first_name || ''} ${props.user.last_name || ''}`.trim() || '—'
})

const roleColor = computed(() => {
  const r = (props.user?.role || '').toUpperCase()
  if (r === 'ADMIN') return '#7e22ce'
  if (r === 'STAFF') return '#1d4ed8'
  return '#0f766e'
})

const roleIcon = computed(() => {
  const r = (props.user?.role || '').toUpperCase()
  if (r === 'ADMIN') return 'admin_panel_settings'
  if (r === 'STAFF') return 'support_agent'
  return 'person'
})

const statusColor = computed(() => {
  const s = (props.user?.status || '').toUpperCase()
  if (s === 'ACTIVE') return '#16a34a'
  if (s === 'INACTIVE') return '#475569'
  if (s === 'SUSPENDED') return '#d97706'
  return '#475569'
})

const ticketsCreatedDisplay = computed(() => {
  if (!props.user) return '0 Tickets'
  const count = props.user.tickets_count ?? props.user.tickets?.length ?? props.user.created_tickets_count ?? 0
  return `${count} ${count === 1 ? 'Ticket' : 'Tickets'}`
})
</script>

<style lang="scss" scoped>
.icon-bg {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #e8f0fe;
  display: flex;
  align-items: center;
  justify-content: center;
}

.line-height-tight { line-height: 1.2; }

.form-label {
  font-size: 0.85rem;
  display: block;
}

.gap-sm { gap: 8px; }
</style>
