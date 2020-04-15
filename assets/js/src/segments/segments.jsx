import React from 'react';
import ReactDOM from 'react-dom';
import { HashRouter, Switch, Route } from 'react-router-dom';

import SegmentList from 'segments/list.jsx';
import DynamicSegmentList from 'segments/dynamic_segments_list.jsx';
import SegmentForm from 'segments/form.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import DynamicSegmentForm from './dynamic_segments_form';

const container = document.getElementById('segments_container');

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <HashRouter>
      <Notices />
      <Switch>
        <Route path="/new" component={SegmentForm} />
        <Route path="/edit/:id" component={SegmentForm} />
        <Route path="/new-segment" component={DynamicSegmentForm} />
        <Route path="/edit-segment/:id" component={DynamicSegmentForm} />
        <Route path="/segments/(.*)?" component={DynamicSegmentList} />
        <Route path="*" component={SegmentList} />
      </Switch>
    </HashRouter>
  </GlobalContext.Provider>
);

if (container) {
  ReactDOM.render(<App />, container);
}
