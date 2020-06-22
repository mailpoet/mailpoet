import React from 'react';
import ReactDOM from 'react-dom';
import apiFetch from '@wordpress/api-fetch';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import Selection from './templates/selection';

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
    // Initialize WP API
    apiFetch.use(apiFetch.createRootURLMiddleware((window as any).wpApiSettings.root));
    apiFetch.use(apiFetch.createNonceMiddleware((window as any).wpApiSettings.nonce));
    ReactDOM.render(
      <React.StrictMode>
        <App />
      </React.StrictMode>,
      appElement
    );
  }
});
