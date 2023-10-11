import React from 'react';

export interface SendContextType {
  saveDraftNewsletter: () => Promise<void>;
}

const defaultValue: SendContextType = {
  saveDraftNewsletter: () => Promise.resolve(),
};

export const SendContext = React.createContext<SendContextType>(defaultValue);
