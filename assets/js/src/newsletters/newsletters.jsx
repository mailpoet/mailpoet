import React from 'react'
import ReactDOM from 'react-dom'
import { Router, Route, IndexRoute, Link, useRouterHistory } from 'react-router'
import { createHashHistory } from 'history'

import NewsletterTypes from 'newsletters/types.jsx'
import NewsletterTemplates from 'newsletters/templates.jsx'
import NewsletterSend from 'newsletters/send.jsx'

import NewsletterTypeStandard from 'newsletters/types/standard.jsx'
import NewsletterTypeWelcome from 'newsletters/types/welcome/welcome.jsx'
import NewsletterTypeNotification from 'newsletters/types/notification/notification.jsx'

import NewsletterListStandard from 'newsletters/listings/standard.jsx'
import NewsletterListWelcome from 'newsletters/listings/welcome.jsx'
import NewsletterListNotification from 'newsletters/listings/notification.jsx'

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
        <IndexRoute component={ NewsletterListStandard } />
        {/* Listings */}
        <Route name="listing/standard" path="standard" component={ NewsletterListStandard } />
        <Route name="listing/welcome" path="welcome" component={ NewsletterListWelcome } />
        <Route name="listing/notification" path="notification" component={ NewsletterListNotification } />
        {/* Newsletter: type selection */}
        <Route path="new" component={ NewsletterTypes } />
        {/* New newsletter: types */}
        <Route name="new/standard" path="new/standard" component={ NewsletterTypeStandard } />
        <Route name="new/welcome" path="new/welcome" component={ NewsletterTypeWelcome } />
        <Route name="new/notification" path="new/notification" component={ NewsletterTypeNotification } />
        {/* Template selection */}
        <Route name="template" path="template/:id" component={ NewsletterTemplates } />
        {/* Sending options */}
        <Route path="send/:id" component={ NewsletterSend } />

        <Route path="*" component={ NewsletterListStandard } />
      </Route>
    </Router>
  ), container);
}
