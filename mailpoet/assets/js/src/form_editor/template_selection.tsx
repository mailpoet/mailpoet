import { StrictMode } from 'react';
import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { Notices } from 'notices/notices.jsx';
import { Selection } from './templates/selection';
import { initStore } from './templates/store/store';
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

const appElement = document.querySelector('#mailpoet_form_edit_templates');
if (appElement) {
  registerTranslations();
  initStore();
  ReactDOM.render(
    <StrictMode>
      <App />
    </StrictMode>,
    appElement,
  );
}
