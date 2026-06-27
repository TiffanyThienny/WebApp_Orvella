import React from 'react';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '../../api/queryClient';

interface AppProvidersProps {
  children: React.ReactNode;
}

/**
 * AppProviders wraps the entire app with:
 * - React Query (server state, caching, background refetch)
 * - Zustand stores are global singletons, no provider needed
 */
export function AppProviders({ children }: AppProvidersProps) {
  return (
    <QueryClientProvider client={queryClient}>
      {children}
    </QueryClientProvider>
  );
}
