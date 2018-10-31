import React from 'react';
import ReactDOM from 'react-dom';
import { Router, Route, IndexRoute, useRouterHistory } from 'react-router';
import { createHashHistory } from 'history';
import PropTypes from 'prop-types';
import SubscriberList from 'subscribers/list.jsx';
import SubscriberForm from 'subscribers/form.jsx';

const history = useRouterHistory(createHashHistory)({ queryKey: false });

class App extends React.Component {
  render() {
    return this.props.children;
  }
}

App.propTypes = {
  children: PropTypes.element.isRequired,
};

const container = document.getElementById('subscribers_container');

if (container) {
  ReactDOM.render((
    <Router history={history}>
      <Route path="/" component={App}>
        <IndexRoute component={SubscriberList} />
        <Route path="new" component={SubscriberForm} />
        <Route path="edit/:id" component={SubscriberForm} />
        <Route path="*" component={SubscriberList} />
      </Route>
    </Router>
  ), container);
}
