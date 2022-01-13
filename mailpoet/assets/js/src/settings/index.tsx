import React from 'react';
import 'parsleyjs';
import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context';
import { initStore } from './store';
import Settings from './settings';

const Entry = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <Settings />
  </GlobalContext.Provider>
);

const container = document.getElementById('settings_container');
if (container) {
  initStore(window);
  ReactDOM.render(<Entry />, container);
}
