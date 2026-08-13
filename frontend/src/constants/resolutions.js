/**
 * Predefined resolutions for ticket fulfillment.
 * Used in staff/admin ticket resolution dialogs.
 */

export const PREDEFINED_RESOLUTIONS = [
  {
    label: 'Provision of technical equipment granted',
    value: 'Provision of technical equipment granted.',
    icon: 'devices',
    color: 'positive',
    category: 'Equipment',
  },
  {
    label: 'Conducted photo documentation',
    value: 'Conducted photo documentation.',
    icon: 'photo_camera',
    color: 'teal',
    category: 'Documentation',
  },
  {
    label: 'Conference Room calendar created',
    value: 'Conference Room calendar created.',
    icon: 'event',
    color: 'primary',
    category: 'Scheduling',
  },
  {
    label: 'Generated Zoom link',
    value: 'Generated Zoom link.',
    icon: 'videocam',
    color: 'info',
    category: 'Scheduling',
  },
  {
    label: 'Created account for the system',
    value: 'Created account for the system.',
    icon: 'person_add',
    color: 'secondary',
    category: 'Account',
  },
  {
    label: 'Password reset completed',
    value: 'Password reset completed.',
    icon: 'lock_reset',
    color: 'deep-purple',
    category: 'Account',
  },
  {
    label: 'System bug fixed and deployed',
    value: 'System bug fixed and deployed to production.',
    icon: 'bug_report',
    color: 'positive',
    category: 'Software',
  },
  {
    label: 'User guidance & support provided',
    value: 'User guidance and technical support provided.',
    icon: 'help_outline',
    color: 'blue-grey',
    category: 'Support',
  },
]

export const PREDEFINED_RESOLUTION_STRINGS = PREDEFINED_RESOLUTIONS.map((r) => r.value)
export default PREDEFINED_RESOLUTIONS
