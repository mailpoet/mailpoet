import { createContext } from 'react';

export const ImportContext = createContext({
  isNewUser: window.mailpoet_is_new_user,
  segments: window.mailpoetSegments,
});
