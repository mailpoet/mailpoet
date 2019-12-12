import React from 'react';
import ReactDOM from 'react-dom';
import { HashRouter, Switch, Route } from 'react-router-dom';

import SegmentList from 'segments/list.jsx';
import SegmentForm from 'segments/form.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';

const container = document.getElementById('segments_container');

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <HashRouter>
      <Notices />
      <Switch>
        <Route path="/new" component={SegmentForm} />
        <Route path="/edit/:id" component={SegmentForm} />
        <Route path="*" component={SegmentList} />
      </Switch>
    </HashRouter>
  </GlobalContext.Provider>
);

if (container) {
  ReactDOM.render(<App />, container);
}
