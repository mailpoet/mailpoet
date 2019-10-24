import React from 'react';
import ReactDOM from 'react-dom';
import Editor from './components/editor.jsx';
import initStore from './store/index.jsx';

const appElement = document.querySelector('#mailpoet_form_edit');

if (appElement) {
  initStore();
  ReactDOM.render(
    <Editor />,
    appElement
  );
}
