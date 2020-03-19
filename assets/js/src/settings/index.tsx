import React from 'react';
import jQuery from 'jquery';
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
  // Select the settings submenu
  // This is temporary and should be removed at the end of settings refactoring
  jQuery('ul.wp-submenu > li:nth-child(7)').addClass('current');
  ReactDOM.render(<Entry />, container);
}
