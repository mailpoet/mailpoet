import React from 'react';
import ReactDOM from 'react-dom';
import apiFetch from '@wordpress/api-fetch';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import Editor from './components/editor.jsx';
import initStore from './store/store.jsx';
import { initBlocks } from './blocks/blocks.jsx';
import initHooks from './hooks';
import initRichText from './rich_text/init.ts';

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <>
      <Notices />
      <Editor />
    </>
  </GlobalContext.Provider>
);

window.addEventListener('DOMContentLoaded', () => {
  const appElement = document.querySelector('#mailpoet_form_edit');
  if (appElement) {
    // Initialize WP API
    apiFetch.use(apiFetch.createRootURLMiddleware(window.wpApiSettings.root));
    apiFetch.use(apiFetch.createNonceMiddleware(window.wpApiSettings.nonce));
    initHooks();
    initStore();
    initBlocks();
    initRichText();
    ReactDOM.render(
      <React.StrictMode>
        <App />
      </React.StrictMode>,
      appElement
    );
  }
});
