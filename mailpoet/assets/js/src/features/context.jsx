import { createContext } from 'react';
import { MailPoet } from 'mailpoet';

export const AppContext = createContext(MailPoet.FeaturesController);
