import React from 'react'
import ReactDOM from 'react-dom'
import { Router, Route, IndexRoute, Link } from 'react-router'
import SubscriberList from 'subscribers/list.jsx'
import SubscriberForm from 'subscribers/form.jsx'
import createHashHistory from 'history/lib/createHashHistory'

let history = createHashHistory({ queryKey: false })

const App = React.createClass({
  render() {
    return this.props.children
  }
});

let container = document.getElementById('subscribers');

if(container) {
  ReactDOM.render((
    <Router history={ history }>
      <Route path="/" component={ App }>
        <IndexRoute component={ SubscriberList } />
        <Route path="new" component={ SubscriberForm } />
        <Route path="edit/:id" component={ SubscriberForm } />
        <Route path="*" component={ SubscriberList } />
      </Route>
    </Router>
  ), container);
}