import React from 'react';
import ReactDOM from 'react-dom';
import { Router, Route, IndexRoute, Link, useRouterHistory } from 'react-router';
import { createHashHistory } from 'history';
import FormList from 'forms/list.jsx';

const history = useRouterHistory(createHashHistory)({ queryKey: false });

const App = React.createClass({
  render() {
    return this.props.children;
  }
});

const container = document.getElementById('forms_container');

if(container) {
  ReactDOM.render((
    <Router history={ history }>
      <Route path="/" component={ App }>
        <IndexRoute component={ FormList } />
        <Route path="*" component={ FormList } />
      </Route>
    </Router>
  ), container);
}