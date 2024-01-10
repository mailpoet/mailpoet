import { createContext } from 'react';
import { useFeaturesContext } from './use-features-context.jsx';
import { useSegmentsContext } from './use-segments-context.jsx';
import { useUsersContext } from './use-users-context.jsx';
import { useNotices } from './use-notices.jsx';

/**
 * Builds the value of the global context.
 * This starts with `use` because it needs to call
 * some React hooks to build some parts of the context.
 */
export function useGlobalContextValue(data) {
  const features = useFeaturesContext(data);
  const segments = useSegmentsContext(data);
  const users = useUsersContext(data);
  const notices = useNotices();
  return {
    features,
    segments,
    users,
    notices,
  };
}

export type GlobalContextValue = ReturnType<typeof useGlobalContextValue>;

export const GlobalContext = createContext<GlobalContextValue>({
  features: null,
  segments: null,
  users: null,
  notices: null,
});
