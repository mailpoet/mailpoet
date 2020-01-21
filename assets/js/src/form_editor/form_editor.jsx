import React from 'react';
import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import Editor from './components/editor.jsx';
import initStore from './store/store.jsx';
import { initBlocks } from './blocks/blocks.jsx';

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <>
      <Notices />
      <Editor />
    </>
  </GlobalContext.Provider>
);

const appElement = document.querySelector('#mailpoet_form_edit');
const oldEditorLinkElement = document.querySelector('#mailpoet_form_edit_old_editor_fallback_link');

if (appElement) {
  try {
    oldEditorLinkElement.style.display = 'none';
    initStore();
    initBlocks();
    ReactDOM.render(
      <React.StrictMode>
        <App />
      </React.StrictMode>,
      appElement
    );
  } catch (e) {
    oldEditorLinkElement.style.display = 'block';
  }
}
