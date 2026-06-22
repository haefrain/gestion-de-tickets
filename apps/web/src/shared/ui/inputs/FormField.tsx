// Campo de formulario reutilizable sobre MUI TextField. Centraliza el patrón
// label asociada + error (helperText con aria-describedby lo gestiona MUI).
// Soporta texto, email, password, multilínea y select (con children <MenuItem/>).

import type { ReactNode } from 'react';
import TextField from '@mui/material/TextField';

export interface FormFieldProps {
  label: string;
  name: string;
  value: string;
  onChange: (value: string) => void;
  error?: string;
  required?: boolean;
  type?: 'text' | 'email' | 'password';
  multiline?: boolean;
  rows?: number;
  disabled?: boolean;
  autoFocus?: boolean;
  fullWidth?: boolean;
  select?: boolean;
  children?: ReactNode;
}

export function FormField({
  label,
  name,
  value,
  onChange,
  error,
  required = false,
  type = 'text',
  multiline = false,
  rows,
  disabled = false,
  autoFocus = false,
  fullWidth = true,
  select = false,
  children,
}: FormFieldProps) {
  return (
    <TextField
      id={name}
      name={name}
      label={label}
      value={value}
      onChange={(event) => onChange(event.target.value)}
      error={error !== undefined && error !== ''}
      helperText={error}
      required={required}
      type={type}
      multiline={multiline}
      rows={rows}
      disabled={disabled}
      autoFocus={autoFocus}
      fullWidth={fullWidth}
      select={select}
      size="small"
      margin="normal"
    >
      {children}
    </TextField>
  );
}
