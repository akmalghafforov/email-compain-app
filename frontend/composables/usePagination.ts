import type { PaginationMeta } from '~/types/subscriber'

export function usePagination(meta: Ref<PaginationMeta | undefined>, page?: Ref<number>) {
  const currentPage = page ?? ref(1)

  function goToPage(page: number) {
    if (page >= 1 && page <= (meta.value?.last_page ?? 1)) {
      currentPage.value = page
    }
  }

  const visiblePages = computed(() => {
    const last = meta.value?.last_page ?? 1
    const current = currentPage.value
    const pages: number[] = []

    let start = Math.max(1, current - 2)
    let end = Math.min(last, current + 2)

    if (current <= 3) {
      end = Math.min(last, 5)
    }
    if (current >= last - 2) {
      start = Math.max(1, last - 4)
    }

    for (let i = start; i <= end; i++) {
      pages.push(i)
    }

    return pages
  })

  return { currentPage, goToPage, visiblePages }
}
