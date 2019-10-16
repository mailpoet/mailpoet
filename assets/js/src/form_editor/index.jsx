import React from 'react';
import ReactDOM from 'react-dom';
import Editor from './components/editor.jsx';

const appElement = document.querySelector('#mailpoet_form_edit');

if (appElement) {
  ReactDOM.render(
    <Editor />,
    appElement
  );
}
