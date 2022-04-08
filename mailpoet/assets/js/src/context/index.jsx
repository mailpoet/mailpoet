import { createContext } from 'react';
import useFeaturesContext from './useFeaturesContext.jsx';
import useSegmentsContext from './useSegmentsContext.jsx';
import useUsersContext from './useUsersContext.jsx';
import useNotices from './useNotices.jsx';

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

export const GlobalContext = createContext({});
