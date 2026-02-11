export function getErrorMessage(err: unknown, fallback = 'An unexpected error occurred'): string {
  if (typeof err === 'string') return err

  if (err && typeof err === 'object') {
    const fetchErr = err as { response?: { _data?: { message?: string; errors?: Record<string, string[]> } } }
    if (fetchErr.response?._data) {
      const data = fetchErr.response._data
      if (data.errors) {
        const messages = Object.values(data.errors).flat()
        if (messages.length) return messages.join(' ')
      }
      if (data.message) return data.message
    }

    if (err instanceof Error) return err.message
  }

  return fallback
}
