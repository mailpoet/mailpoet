import React from 'react'
import ReactDOM from 'react-dom'
import { Router, Route, IndexRedirect, Link, useRouterHistory } from 'react-router'
import { createHashHistory } from 'history'
import Hooks from 'wp-js-hooks'

import NewsletterTypes from 'newsletters/types.jsx'
import NewsletterTemplates from 'newsletters/templates.jsx'
import NewsletterSend from 'newsletters/send.jsx'

import NewsletterTypeStandard from 'newsletters/types/standard.jsx'
import NewsletterTypeWelcome from 'newsletters/types/welcome/welcome.jsx'
import NewsletterTypeNotification from 'newsletters/types/notification/notification.jsx'

import NewsletterListStandard from 'newsletters/listings/standard.jsx'
import NewsletterListWelcome from 'newsletters/listings/welcome.jsx'
import NewsletterListNotification from 'newsletters/listings/notification.jsx'
import NewsletterListNotificationHistory from 'newsletters/listings/notification_history.jsx'

const history = useRouterHistory(createHashHistory)({ queryKey: false });

const App = React.createClass({
  render() {
    return this.props.children;
  }
});

const container = document.getElementById('newsletters_container');

if(container) {
  let extra_routes = [];
  extra_routes = Hooks.applyFilters('mailpoet_newsletters_before_router', extra_routes);

  const mailpoet_listing = ReactDOM.render((
    <Router history={ history }>
      <Route path="/" component={ App }>
        <IndexRedirect to="standard" />
        {/* Listings */}
        <Route path="standard(/)**" params={{ tab: 'standard' }} component={ NewsletterListStandard } />
        <Route path="welcome(/)**" component={ NewsletterListWelcome } />
        <Route path="notification/history/:parent_id(/)**" component={ NewsletterListNotificationHistory } />
        <Route path="notification(/)**" component={ NewsletterListNotification } />
        {/* Newsletter: type selection */}
        <Route path="new" component={ NewsletterTypes } />
        {/* New newsletter: types */}
        <Route path="new/standard" component={ NewsletterTypeStandard } />
        <Route path="new/welcome" component={ NewsletterTypeWelcome } />
        <Route path="new/notification" component={ NewsletterTypeNotification } />
        {/* Template selection */}
        <Route name="template" path="template/:id" component={ NewsletterTemplates } />
        {/* Sending options */}
        <Route path="send/:id" component={ NewsletterSend } />
        {/* Extra routes */}
        { extra_routes.map(rt => <Route key={rt.path} path={rt.path} component={rt.component} />) }
      </Route>
    </Router>
  ), container);

  window.mailpoet_listing = mailpoet_listing;
}
