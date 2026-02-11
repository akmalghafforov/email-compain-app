export interface Template {
  id: number
  name: string
  engine: string
  subject_template: string
  body_content: string
  metadata: Record<string, unknown> | null
  created_at: string
  updated_at: string
}
