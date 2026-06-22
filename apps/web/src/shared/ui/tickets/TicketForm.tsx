// Formulario de creación/edición de ticket (organismo) compuesto sobre FormField.
// Validación en cliente; los errores del backend (RFC 7807) se inyectan vía `errors`.
import { useState, type FormEvent } from 'react';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import MenuItem from '@mui/material/MenuItem';
import Stack from '@mui/material/Stack';
import { FormField } from '../inputs/FormField';
import { PRIORITIES, type Priority } from '../../api/types';
import { PRIORITY_LABEL } from '../../theme/tokens';

export interface TicketFormValues {
  title: string;
  description: string;
  priority: Priority;
  category: string;
}

export interface TicketFormProps {
  mode: 'create' | 'edit';
  initialValues?: Partial<TicketFormValues>;
  submitting?: boolean;
  /** Errores por campo del backend (RFC 7807). */
  errors?: Record<string, string>;
  onSubmit: (values: TicketFormValues) => void;
  onCancel?: () => void;
}

const DEFAULTS: TicketFormValues = { title: '', description: '', priority: 'medium', category: '' };

export function TicketForm({
  mode,
  initialValues,
  submitting = false,
  errors = {},
  onSubmit,
  onCancel,
}: TicketFormProps) {
  const [values, setValues] = useState<TicketFormValues>({ ...DEFAULTS, ...initialValues });
  const [touched, setTouched] = useState(false);

  const localErrors: Record<string, string> = {};
  if (touched) {
    if (values.title.trim() === '') {
      localErrors.title = 'El título es obligatorio.';
    }
    if (values.category.trim() === '') {
      localErrors.category = 'La categoría es obligatoria.';
    }
  }
  const allErrors = { ...localErrors, ...errors };

  function setField<K extends keyof TicketFormValues>(key: K, value: TicketFormValues[K]): void {
    setValues((previous) => ({ ...previous, [key]: value }));
  }

  function handleSubmit(event: FormEvent): void {
    event.preventDefault();
    setTouched(true);
    if (values.title.trim() === '' || values.category.trim() === '') {
      return;
    }
    onSubmit(values);
  }

  return (
    <Box component="form" onSubmit={handleSubmit} noValidate>
      <FormField
        name="title"
        label="Título"
        value={values.title}
        onChange={(value) => setField('title', value)}
        error={allErrors.title}
        required
        autoFocus
        disabled={submitting}
      />
      <FormField
        name="description"
        label="Descripción"
        value={values.description}
        onChange={(value) => setField('description', value)}
        error={allErrors.description}
        multiline
        rows={4}
        disabled={submitting}
      />
      <FormField
        name="priority"
        label="Prioridad"
        value={values.priority}
        onChange={(value) => setField('priority', value as Priority)}
        select
        disabled={submitting}
      >
        {PRIORITIES.map((priority) => (
          <MenuItem key={priority} value={priority}>
            {PRIORITY_LABEL[priority]}
          </MenuItem>
        ))}
      </FormField>
      <FormField
        name="category"
        label="Categoría"
        value={values.category}
        onChange={(value) => setField('category', value)}
        error={allErrors.category}
        required
        disabled={submitting}
      />
      <Stack direction="row" spacing={1} sx={{ mt: 2, justifyContent: 'flex-end' }}>
        {onCancel !== undefined ? (
          <Button onClick={onCancel} disabled={submitting}>
            Cancelar
          </Button>
        ) : null}
        <Button type="submit" variant="contained" disabled={submitting}>
          {mode === 'create' ? 'Crear ticket' : 'Guardar cambios'}
        </Button>
      </Stack>
    </Box>
  );
}
