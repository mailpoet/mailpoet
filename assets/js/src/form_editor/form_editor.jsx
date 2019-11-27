import React from 'react';
import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Editor from './components/editor.jsx';
import initStore from './store/store.jsx';

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <Editor />
  </GlobalContext.Provider>
);

const appElement = document.querySelector('#mailpoet_form_edit');

if (appElement) {
  initStore();
  ReactDOM.render(
    <App />,
    appElement
  );
}
