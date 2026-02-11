import type { PaginatedResponse } from '~/types/pagination'

const PER_PAGE = 15

export function useListPage<T>(path: string) {
  const config = useRuntimeConfig()
  const currentPage = ref(1)

  const url = computed(
    () => `${config.public.apiBase}${path}?page=${currentPage.value}&per_page=${PER_PAGE}`
  )

  const { data, status, error } = useFetch<PaginatedResponse<T>>(url)

  const items = computed(() => data.value?.data ?? [])
  const meta = computed(() => data.value?.meta)

  return { currentPage, items, meta, status, error }
}
