import React from 'react';
import ReactDOM from 'react-dom';
import { Router, Route, IndexRoute, useRouterHistory } from 'react-router';
import { createHashHistory } from 'history';

import SegmentList from 'segments/list.jsx';
import SegmentForm from 'segments/form.jsx';

const history = useRouterHistory(createHashHistory)({ queryKey: false });

const App = React.createClass({
  render() {
    return this.props.children;
  }
});

const container = document.getElementById('segments_container');

if(container) {
  ReactDOM.render((
    <Router history={ history }>
      <Route path="/" component={ App }>
        <IndexRoute component={ SegmentList } />
        <Route path="new" component={ SegmentForm } />
        <Route path="edit/:id" component={ SegmentForm } />
        <Route path="*" component={ SegmentList } />
      </Route>
    </Router>
  ), container);
}