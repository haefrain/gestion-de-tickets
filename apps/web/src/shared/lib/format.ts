// Utilidades de formato reutilizables (fechas en español).
export function formatDateTime(iso: string): string {
  return new Intl.DateTimeFormat('es', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso));
}

export function formatDate(iso: string): string {
  return new Intl.DateTimeFormat('es', { dateStyle: 'medium' }).format(new Date(iso));
}
