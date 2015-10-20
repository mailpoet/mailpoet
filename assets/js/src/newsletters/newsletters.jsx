import React from 'react'
import ReactDOM from 'react-dom'
import { Router, Route, IndexRoute, Link } from 'react-router'
import NewsletterList from 'newsletters/list.jsx'
import NewsletterTypes from 'newsletters/types.jsx'
import NewsletterTemplates from 'newsletters/templates.jsx'
import NewsletterSend from 'newsletters/send.jsx'
import createHashHistory from 'history/lib/createHashHistory'

let history = createHashHistory({ queryKey: false })

const App = React.createClass({
  render() {
    return this.props.children
  }
});

let container = document.getElementById('newsletters');

if(container) {
  ReactDOM.render((
    <Router history={ history }>
      <Route path="/" component={ App }>
        <IndexRoute component={ NewsletterList } />
        <Route path="new" component={ NewsletterTypes } />
        <Route path="new/:type" component={ NewsletterTemplates } />
        <Route path="send/:id" component={ NewsletterSend } />
        <Route path="*" component={ NewsletterList } />
      </Route>
    </Router>
  ), container);
}