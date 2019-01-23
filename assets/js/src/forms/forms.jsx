import React from 'react';
import ReactDOM from 'react-dom';
import { Route, HashRouter } from 'react-router-dom';
import FormList from './list.jsx';

const container = document.getElementById('forms_container');

if (container) {
  ReactDOM.render(
    <HashRouter>
      <Route path="*" component={FormList} />
    </HashRouter>,
    container
  );
}
