import React from 'react';
import ReactDOM from 'react-dom';
import { HashRouter, Switch, Route } from 'react-router-dom';

import DynamicSegmentList from './list.jsx';
import DynamicSegmentForm from './form.jsx';

const container = document.getElementById('dynamic_segments_container');

if (container) {
  ReactDOM.render(
    (
      <HashRouter>
        <Switch>
          <Route path="/new" component={DynamicSegmentForm} />
          <Route path="/edit/:id" component={DynamicSegmentForm} />
          <Route path="*" component={DynamicSegmentList} />
        </Switch>
      </HashRouter>
    ), container
  );
}
