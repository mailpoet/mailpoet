import React from 'react';
import getFeaturesContext from './getFeaturesContext.jsx';
import getSegmentsContext from './getSegmentsContext.jsx';
import getUsersContext from './getUsersContext.jsx';

/**
 * Builds the value of the global context.
 * This starts with `use` because it needs to call
 * some React hooks to build some parts of the context.
 */
export function useGlobalContextValue(data) {
  const features = getFeaturesContext(data);
  const segments = getSegmentsContext(data);
  const users = getUsersContext(data);
  return { features, segments, users };
}

export const GlobalContext = React.createContext(useGlobalContextValue(window));
