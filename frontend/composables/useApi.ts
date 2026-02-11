export function useApi() {
  const config = useRuntimeConfig()
  const base = config.public.apiBase as string

  function url(path: string): string {
    return `${base}${path}`
  }

  async function get<T>(path: string): Promise<T> {
    return $fetch<T>(url(path))
  }

  async function post<T>(path: string, body?: Record<string, unknown>): Promise<T> {
    return $fetch<T>(url(path), { method: 'POST', body })
  }

  async function put<T>(path: string, body?: Record<string, unknown>): Promise<T> {
    return $fetch<T>(url(path), { method: 'PUT', body })
  }

  return { url, get, post, put }
}
