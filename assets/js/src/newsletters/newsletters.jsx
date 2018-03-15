import React from 'react';
import ReactDOM from 'react-dom';
import { Router, Route, IndexRedirect, useRouterHistory } from 'react-router';
import { createHashHistory } from 'history';
import Hooks from 'wp-js-hooks';
import _ from 'underscore';

import NewsletterTypes from 'newsletters/types.jsx';
import NewsletterTemplates from 'newsletters/templates.jsx';
import NewsletterSend from 'newsletters/send.jsx';
import NewsletterTypeStandard from 'newsletters/types/standard.jsx';
import NewsletterTypeNotification from 'newsletters/types/notification/notification.jsx';
import AutomaticEmailEventsList from 'newsletters/types/automatic_emails/events_list.jsx';
import NewsletterListStandard from 'newsletters/listings/standard.jsx';
import NewsletterListWelcome from 'newsletters/listings/welcome.jsx';
import NewsletterListNotification from 'newsletters/listings/notification.jsx';
import NewsletterListNotificationHistory from 'newsletters/listings/notification_history.jsx';

const history = useRouterHistory(createHashHistory)({ queryKey: false });

const App = React.createClass({
  render() {
    return this.props.children;
  },
});

const container = document.getElementById('newsletters_container');

const getAutomaticEmailsRoutes = () => {
  if (!window.mailpoet_automatic_emails) return null;

  return _.map(window.mailpoet_automatic_emails, automaticEmail => ({
    path: `new/${automaticEmail.slug}`,
    name: automaticEmail.slug,
    component: AutomaticEmailEventsList,
    data: {
      automaticEmail,
    },
  }));
};

if (container) {
  let routes = [
    /* Listings */
    {
      path: 'standard(/)**',
      params: { tab: 'standard' },
      component: NewsletterListStandard,
    },
    {
      path: 'welcome(/)**',
      component: NewsletterListWelcome,
    },
    {
      path: 'notification/history/:parent_id(/)**',
      component: NewsletterListNotificationHistory,
    },
    {
      path: 'notification(/)**',
      component: NewsletterListNotification,
    },
    /* Newsletter: type selection */
    {
      path: 'new',
      component: NewsletterTypes,
    },
    /* New newsletter: types */
    {
      path: 'new/standard',
      component: NewsletterTypeStandard,
    },
    {
      path: 'new/notification',
      component: NewsletterTypeNotification,
    },
    /* Template selection */
    {
      name: 'template',
      path: 'template/:id',
      component: NewsletterTemplates,
    },
    /* Sending options */
    {
      path: 'send/:id',
      component: NewsletterSend,
    },
  ];

  routes = Hooks.applyFilters('mailpoet_newsletters_before_router', [...routes, ...getAutomaticEmailsRoutes()]);

  const mailpoetListing = ReactDOM.render((
    <Router history={history}>
      <Route path="/" component={App}>
        <IndexRedirect to="standard" />
        {routes.map(route => (
          <Route
            key={route.path}
            path={route.path}
            component={route.component}
            name={route.name || null}
            params={route.params || null}
            data={route.data || null}
          />
        ))}
      </Route>
    </Router>
  ), container);

  window.mailpoet_listing = mailpoetListing;
}
