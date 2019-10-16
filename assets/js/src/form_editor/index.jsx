import React from 'react';
import ReactDOM from 'react-dom';

const appElement = document.querySelector('#mailpoet_form_edit');

if (appElement) {
  ReactDOM.render(
    <h1>
      Here comes editor for:
      {window.mailpoet_form_data.name}
    </h1>,
    appElement
  );
}
