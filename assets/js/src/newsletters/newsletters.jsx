import React from 'react'
import ReactDOM from 'react-dom'
import { Router, Route, IndexRoute, Link, useRouterHistory } from 'react-router'
import { createHashHistory } from 'history'
import NewsletterList from 'newsletters/list.jsx'
import NewsletterTypes from 'newsletters/types.jsx'
import NewsletterTemplates from 'newsletters/templates.jsx'
import NewsletterSend from 'newsletters/send.jsx'
import NewsletterStandard from 'newsletters/types/standard.jsx'
import NewsletterWelcome from 'newsletters/types/welcome/welcome.jsx'
import NewsletterNotification from 'newsletters/types/notification/notification.jsx'

const history = useRouterHistory(createHashHistory)({ queryKey: false });

const App = React.createClass({
  render() {
    return this.props.children
  }
});

const container = document.getElementById('newsletters_container');

if(container) {
  ReactDOM.render((
    <Router history={ history }>
      <Route path="/" component={ App }>
        <IndexRoute component={ NewsletterList } />
        <Route path="new" component={ NewsletterTypes } />
        <Route name="standard" path="new/standard" component={ NewsletterStandard } />
        <Route name="welcome" path="new/welcome" component={ NewsletterWelcome } />
        <Route name="notification" path="new/notification" component={ NewsletterNotification } />
        <Route name="template" path="template/:id" component={ NewsletterTemplates } />
        <Route path="send/:id" component={ NewsletterSend } />
        <Route path="filter[:filter]" component={ NewsletterList } />
        <Route path="*" component={ NewsletterList } />
      </Route>
    </Router>
  ), container);
}
