// Barra de búsqueda reutilizable. Envía por Enter (form role="search") o por el botón.
// a11y: label/aria-label y botón con nombre accesible.

import TextField from '@mui/material/TextField';
import InputAdornment from '@mui/material/InputAdornment';
import IconButton from '@mui/material/IconButton';
import SearchIcon from '@mui/icons-material/Search';

export interface SearchBarProps {
  value: string;
  onChange: (value: string) => void;
  onSubmit?: (value: string) => void;
  label?: string;
  placeholder?: string;
  disabled?: boolean;
}

export function SearchBar({
  value,
  onChange,
  onSubmit,
  label = 'Buscar',
  placeholder = 'Buscar tickets…',
  disabled = false,
}: SearchBarProps) {
  return (
    <form
      role="search"
      onSubmit={(event) => {
        event.preventDefault();
        onSubmit?.(value);
      }}
    >
      <TextField
        value={value}
        onChange={(event) => onChange(event.target.value)}
        label={label}
        placeholder={placeholder}
        disabled={disabled}
        type="search"
        size="small"
        fullWidth
        slotProps={{
          input: {
            endAdornment: (
              <InputAdornment position="end">
                <IconButton type="submit" aria-label="Buscar" edge="end" disabled={disabled}>
                  <SearchIcon />
                </IconButton>
              </InputAdornment>
            ),
          },
        }}
      />
    </form>
  );
}
