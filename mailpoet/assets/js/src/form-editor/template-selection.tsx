import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { Selection } from './templates/selection';
import { createStore } from './templates/store/store';
import { registerTranslations } from '../common';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <>
        <Notices />
        <Selection />
      </>
    </GlobalContext.Provider>
  );
}

const container = document.querySelector('#mailpoet_form_edit_templates');
if (container) {
  registerTranslations();
  createStore();
  const root = createRoot(container);
  root.render(
    <StrictMode>
      <App />
    </StrictMode>,
  );
}
