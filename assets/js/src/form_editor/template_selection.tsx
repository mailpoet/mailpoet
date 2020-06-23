import React from 'react';
import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import Selection from './templates/selection';

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <>
      <Notices />
      <Selection
        templates={(window as any).mailpoet_templates}
        formEditorUrl={(window as any).mailpoet_form_edit_url}
      />
    </>
  </GlobalContext.Provider>
);

window.addEventListener('DOMContentLoaded', () => {
  const appElement = document.querySelector('#mailpoet_form_edit_templates');
  if (appElement) {
    ReactDOM.render(
      <React.StrictMode>
        <App />
      </React.StrictMode>,
      appElement
    );
  }
});
