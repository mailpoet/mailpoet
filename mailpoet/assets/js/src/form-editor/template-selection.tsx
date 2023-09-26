import { StrictMode } from 'react';
import ReactDOM from 'react-dom';
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

const appElement = document.querySelector('#mailpoet_form_edit_templates');
if (appElement) {
  registerTranslations();
  createStore();
  ReactDOM.render(
    <StrictMode>
      <App />
    </StrictMode>,
    appElement,
  );
}
