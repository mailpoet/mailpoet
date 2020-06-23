import React from 'react';
import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import Selection from './templates/selection';
import initStore from './templates/store';

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <>
      <Notices />
      <Selection />
    </>
  </GlobalContext.Provider>
);

window.addEventListener('DOMContentLoaded', () => {
  const appElement = document.querySelector('#mailpoet_form_edit_templates');
  if (appElement) {
    initStore();
    ReactDOM.render(
      <React.StrictMode>
        <App />
      </React.StrictMode>,
      appElement
    );
  }
});
