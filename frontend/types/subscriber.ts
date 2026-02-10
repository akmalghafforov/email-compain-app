export interface Subscriber {
  id: number
  email: string
  name: string | null
  status: string
  metadata: Record<string, unknown> | null
  subscribed_at: string | null
  unsubscribed_at: string | null
  created_at: string
  updated_at: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface PaginatedResponse<T = Subscriber> {
  data: T[]
  meta: PaginationMeta
}
