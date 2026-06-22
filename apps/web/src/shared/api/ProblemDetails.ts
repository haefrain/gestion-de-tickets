// Modelo de error RFC 7807 (application/problem+json) que devuelve la API
// (docs/api/api-design.md §4) y su mapeo a errores por campo de formulario.

export interface FieldError {
  field: string;
  message: string;
}

export interface ProblemDetails {
  type: string;
  title: string;
  status: number;
  detail?: string;
  instance?: string;
  errors?: FieldError[];
}

/** Error tipado lanzado por apiClient ante respuestas no-OK. */
export class ApiError extends Error {
  readonly status: number;
  readonly problem: ProblemDetails;

  constructor(status: number, problem: ProblemDetails) {
    super(problem.detail ?? problem.title);
    this.name = 'ApiError';
    this.status = status;
    this.problem = problem;
  }

  /** Errores de validación por campo (extensión RFC 7807) como mapa `campo -> mensaje`. */
  fieldErrors(): Record<string, string> {
    const map: Record<string, string> = {};
    for (const error of this.problem.errors ?? []) {
      map[error.field] = error.message;
    }
    return map;
  }
}
