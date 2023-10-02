import React from 'react';

interface SendContextType {
  saveDraftNewsletter: (afterSaveCallback: () => void) => void;
}

const defaultValue: SendContextType = {
  saveDraftNewsletter: () => {},
};

export const SendContext = React.createContext<SendContextType>(defaultValue);
