import React from 'react';
import ReactDOM from 'react-dom';
import { HashRouter, Switch, Route } from 'react-router-dom';

import SegmentList from 'segments/list.jsx';
import SegmentForm from 'segments/form.jsx';

const container = document.getElementById('segments_container');

if (container) {
  ReactDOM.render((
    <HashRouter>
      <Switch>
        <Route path="/new" component={SegmentForm} />
        <Route path="/edit/:id" component={SegmentForm} />
        <Route path="*" component={SegmentList} />
      </Switch>
    </HashRouter>
  ), container);
}
