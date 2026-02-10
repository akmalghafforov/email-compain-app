export interface Campaign {
    id: number
    name: string
    subject: string
    template_id: number
    sender_channel: string
    status: string
    scheduled_at: string | null
    sent_at: string | null
    created_at: string
    updated_at: string
}
