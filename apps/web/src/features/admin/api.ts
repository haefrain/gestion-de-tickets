// Capa de datos de administración de usuarios (HU-L1-E2-03). TanStack Query sobre /users.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../../shared/api/apiClient';

export interface AdminUser {
  id: string;
  email: string;
  name: string | null;
  roles: string[];
  active: boolean;
}

interface RawPage {
  data: AdminUser[];
  page: { limit: number; next_cursor: string | null; has_more: boolean };
}

const usersKey = ['admin', 'users'] as const;

export function useUsers() {
  return useQuery({
    queryKey: usersKey,
    queryFn: async () => {
      const raw = await apiClient.get<RawPage>('/users?limit=50');
      return raw.data;
    },
  });
}

export interface UpdateUserInput {
  id: string;
  roles?: string[];
  active?: boolean;
}

export function useUpdateUser() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (input: UpdateUserInput) =>
      apiClient.patch<void>(`/users/${input.id}`, { roles: input.roles, active: input.active }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: usersKey }),
  });
}
