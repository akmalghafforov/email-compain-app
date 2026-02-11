export function formatDate(value: string | null | undefined): string {
  if (!value) return '\u2014'
  return new Date(value).toLocaleDateString()
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) return '\u2014'
  return new Date(value).toLocaleString()
}

export function parseDateParts(isoString: string): { date: string; time: string } {
  const d = new Date(isoString)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return {
    date: `${year}-${month}-${day}`,
    time: `${hours}:${minutes}`,
  }
}

export function buildISOString(date: string, time: string): string {
  return `${date}T${time}:00`
}

export function formatPercent(value: number): string {
  return `${(value * 100).toFixed(1)}%`
}
