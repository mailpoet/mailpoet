import React from 'react';

const ImportContext = React.createContext({
  isNewUser: window.mailpoet_is_new_user,
  segments: window.mailpoetSegments,
});

export default ImportContext;
