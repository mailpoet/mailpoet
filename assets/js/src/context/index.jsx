import React from 'react';
import getFeaturesContext from './getFeaturesContext.jsx';
import getSegmentsContext from './getSegmentsContext.jsx';
import getUsersContext from './getUsersContext.jsx';
import useNotices from './useNotices.jsx';

/**
 * Builds the value of the global context.
 * This starts with `use` because it needs to call
 * some React hooks to build some parts of the context.
 */
export function useGlobalContextValue(data) {
  const features = getFeaturesContext(data);
  const segments = getSegmentsContext(data);
  const users = getUsersContext(data);
  const notices = useNotices();
  return {
    features, segments, users, notices,
  };
}

export const GlobalContext = React.createContext({});
