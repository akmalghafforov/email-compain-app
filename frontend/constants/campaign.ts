export const SENDER_CHANNELS = [
  { value: 'smtp', label: 'SMTP' },
  { value: 'sendgrid', label: 'SendGrid' },
  { value: 'mailgun', label: 'Mailgun' },
] as const

export const TEMPLATE_ENGINES = [
  { value: 'blade', label: 'Blade' },
  { value: 'twig', label: 'Twig' },
  { value: 'markdown', label: 'Markdown' },
  { value: 'mjml', label: 'MJML' },
] as const

export const CUSTOM_TEMPLATE = 'custom'
export const DEFAULT_SCHEDULED_TIME = '09:00'
