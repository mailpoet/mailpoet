import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import apiFetch from '@wordpress/api-fetch';
import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { Editor } from './components/editor.jsx';
import { initStore } from './store/store';
import { initBlocks } from './blocks/blocks.jsx';
import { initHooks } from './hooks';
import { initTranslations } from './translations';
import { initRichText } from './rich-text/init.ts';
import './template-selection';
import { registerTranslations } from '../common';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <>
        <Notices />
        <Editor />
      </>
    </GlobalContext.Provider>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const appElement = document.querySelector('#mailpoet_form_edit');
  if (appElement) {
    const root = createRoot(appElement);
    // Initialize WP API
    apiFetch.use(apiFetch.createRootURLMiddleware(window.wpApiSettings.root));
    apiFetch.use(apiFetch.createNonceMiddleware(window.wpApiSettings.nonce));
    initHooks();
    initStore();
    initBlocks();
    initRichText();
    initTranslations(window.mailpoet_translations);
    registerTranslations();
    root.render(
      <StrictMode>
        <App />
      </StrictMode>,
    );
  }
});
