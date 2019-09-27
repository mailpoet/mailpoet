import React from 'react';
import getFeaturesContext from './getFeaturesContext';
import getConstantsContext from './getConstantsContext';

/**
 * Builds the value of the global context.
 * This starts with `use` because it needs to call
 * some React hooks to build some parts of the context.
 */
export function useGlobalContextValue(data) {
  const features = getFeaturesContext(data);
  const constants = getConstantsContext(data);
  return { features, constants };
}

export const GlobalContext = React.createContext(useGlobalContextValue(window));
