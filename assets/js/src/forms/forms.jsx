import React from 'react';
import ReactDOM from 'react-dom';
import { Route, HashRouter } from 'react-router-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import FormList from './list.jsx';

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <HashRouter>
      <Notices />
      <Route path="*" component={FormList} />
    </HashRouter>
  </GlobalContext.Provider>
);

const container = document.getElementById('forms_container');

if (container) {
  ReactDOM.render(
    <App />,
    container
  );
}
