import { createContext } from 'react';

const ImportContext = createContext({
  isNewUser: window.mailpoet_is_new_user,
  segments: window.mailpoetSegments,
});

export default ImportContext;
