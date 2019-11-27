import React from 'react';
import ReactDOM from 'react-dom';
import { HashRouter, Switch, Route } from 'react-router-dom';

import DynamicSegmentList from 'dynamic_segments/list.jsx';
import DynamicSegmentForm from 'dynamic_segments/form.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <HashRouter>
      <Switch>
        <Route path="/new" component={DynamicSegmentForm} />
        <Route path="/edit/:id" component={DynamicSegmentForm} />
        <Route path="*" component={DynamicSegmentList} />
      </Switch>
    </HashRouter>
  </GlobalContext.Provider>
);

const container = document.getElementById('dynamic_segments_container');

if (container) {
  ReactDOM.render(<App />, container);
}
