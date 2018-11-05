import React from 'react';
import ReactDOM from 'react-dom';
import { Router, Route, IndexRoute, useRouterHistory } from 'react-router';
import PropTypes from 'prop-types';
import { createHashHistory } from 'history';
import FormList from './list.jsx';

const history = useRouterHistory(createHashHistory)({ queryKey: false });

class App extends React.Component {
  render() {
    return this.props.children;
  }
}

App.propTypes = {
  children: PropTypes.element.isRequired,
};

const container = document.getElementById('forms_container');

if (container) {
  ReactDOM.render((
    <Router history={history}>
      <Route path="/" component={App}>
        <IndexRoute component={FormList} />
        <Route path="*" component={FormList} />
      </Route>
    </Router>
  ), container);
}
