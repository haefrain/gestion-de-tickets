// Capa de datos del perfil propio (HU-L1-E3-01). TanStack Query sobre /me.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../../shared/api/apiClient';

export interface Profile {
  id: string;
  email: string;
  name: string | null;
  roles: string[];
}

const profileKey = ['profile'] as const;

export function useProfile() {
  return useQuery({
    queryKey: profileKey,
    queryFn: () => apiClient.get<Profile>('/me'),
  });
}

export interface UpdateProfileInput {
  name?: string;
  password?: string;
}

export function useUpdateProfile() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (input: UpdateProfileInput) => apiClient.patch<Profile>('/me', input),
    onSuccess: (profile) => queryClient.setQueryData(profileKey, profile),
  });
}
