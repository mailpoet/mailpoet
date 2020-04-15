import React from 'react';
import ReactDOM from 'react-dom';
import { HashRouter, Switch, Route } from 'react-router-dom';

import DynamicSegmentList from 'dynamic_segments/list.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <HashRouter>
      <Notices />
      <Switch>
        <Route path="*" component={DynamicSegmentList} />
      </Switch>
    </HashRouter>
  </GlobalContext.Provider>
);

const container = document.getElementById('dynamic_segments_container');

if (container) {
  ReactDOM.render(<App />, container);
}
