import React from 'react';

export interface SendContextType {
  saveDraftNewsletter: (onSuccess: () => void) => void;
}

const defaultValue: SendContextType = {
  saveDraftNewsletter: () => {},
};

export const SendContext = React.createContext<SendContextType>(defaultValue);
