import React from 'react';
import 'parsleyjs';
import ReactDOM from 'react-dom';
import { HashRouter } from 'react-router-dom';
import { GlobalContext, useGlobalContextValue } from 'context';
import { initStore } from './store';
import Settings from './settings';

const Entry = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <HashRouter>
      <Settings />
    </HashRouter>
  </GlobalContext.Provider>
);

const container = document.getElementById('settings_container');
if (container) {
  initStore(window);
  ReactDOM.render(<Entry />, container);
}
