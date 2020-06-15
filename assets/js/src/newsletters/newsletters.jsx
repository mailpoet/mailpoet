import React from 'react';
import ReactDOM from 'react-dom';
import {
  HashRouter, Switch, Route, Redirect, useParams,
} from 'react-router-dom';
import Hooks from 'wp-js-hooks';
import MailPoet from 'mailpoet';
import _ from 'underscore';

import NewsletterTypes from 'newsletters/types.jsx';
import NewsletterTemplates from 'newsletters/templates.jsx';
import NewsletterSend from 'newsletters/send.jsx';
import NewsletterCongratulate from 'newsletters/send/congratulate/congratulate.jsx';
import NewsletterTypeStandard from 'newsletters/types/standard.jsx';
import NewsletterTypeNotification from 'newsletters/types/notification/notification.jsx';
import NewsletterTypeWelcome from 'newsletters/types/welcome/welcome.jsx';
import AutomaticEmailEventsList from 'newsletters/types/automatic_emails/events_list.jsx';
import NewsletterListStandard from 'newsletters/listings/standard.jsx';
import NewsletterListWelcome from 'newsletters/listings/welcome.jsx';
import NewsletterListNotification from 'newsletters/listings/notification.jsx';
import NewsletterListNotificationHistory from 'newsletters/listings/notification_history.jsx';
import NewsletterSendingStatus from 'newsletters/sending_status.jsx';
import CampaignStatsPage from 'newsletters/campaign_stats/page.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import RoutedTabs from 'common/tabs/routed_tabs';
import Tab from 'common/tabs/tab';
import withNpsPoll from 'nps_poll.jsx';

const getAutomaticEmailsRoutes = () => {
  if (!window.mailpoet_automatic_emails) return [];

  return _.map(window.mailpoet_automatic_emails, (automaticEmail) => ({
    path: `new/${automaticEmail.slug}`,
    name: automaticEmail.slug,
    component: AutomaticEmailEventsList,
    data: {
      email: automaticEmail,
    },
  }));
};

const trackTabSwitch = (tabKey) => MailPoet.trackEvent(
  `Tab Emails > ${tabKey} clicked`,
  { 'MailPoet Free version': window.mailpoet_version },
);

const Tabs = withNpsPoll(() => {
  const { parentId } = useParams();
  return (
    <RoutedTabs activeKey="standard" routerType="switch-only" onSwitch={(tabKey) => trackTabSwitch(tabKey)}>
      <Tab
        key="standard"
        route="standard/(.*)?"
        title={MailPoet.I18n.t('tabStandardTitle')}
        automationId={MailPoet.I18n.t('tabStandardTitle')}
      >
        <NewsletterListStandard />
      </Tab>
      <Tab
        key="welcome"
        route="welcome/(.*)?"
        title={MailPoet.I18n.t('tabWelcomeTitle')}
        automationId={MailPoet.I18n.t('tabWelcomeTitle')}
      >
        <NewsletterListWelcome />
      </Tab>
      <Tab
        key="notification"
        route="notification/(.*)?"
        title={MailPoet.I18n.t('tabNotificationTitle')}
        automationId={MailPoet.I18n.t('tabNotificationTitle')}
      >
        {
          parentId
            ? <NewsletterListNotificationHistory />
            : <NewsletterListNotification />
        }
      </Tab>
    </RoutedTabs>
  );
});

const routes = Hooks.applyFilters('mailpoet_newsletters_before_router', [
  /* Listings */
  {
    path: '/notification/history/:parentId/(.*)?',
    component: Tabs,
  },
  {
    path: '/(standard|welcome|notification)/(.*)?',
    component: Tabs,
  },
  /* New newsletter: types */
  {
    path: '/new/standard',
    component: NewsletterTypeStandard,
  },
  {
    path: '/new/notification',
    component: NewsletterTypeNotification,
  },
  {
    path: '/new/welcome',
    component: NewsletterTypeWelcome,
  },
  /* Newsletter: type selection */
  {
    path: '/new',
    component: NewsletterTypes,
  },
  /* Template selection */
  {
    name: 'template',
    path: '/template/:id',
    component: NewsletterTemplates,
  },
  /* congratulate */
  {
    path: '/send/congratulate/:id',
    component: NewsletterCongratulate,
  },
  /* Sending options */
  {
    path: '/send/:id',
    component: NewsletterSend,
  },
  {
    path: '/sending-status/:id/(.*)?',
    component: NewsletterSendingStatus,
  },
  {
    path: '/stats/:id/(.*)?',
    component: CampaignStatsPage,
  },
  ...getAutomaticEmailsRoutes(),
]);

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <HashRouter>
      <Notices />
      <Switch>
        <Route exact path="/" render={() => <Redirect to={window.mailpoet_newsletters_count === 0 ? '/new' : '/standard'} />} />
        {routes.map((route) => (
          <Route
            key={route.path}
            path={route.path}
            component={route.component}
            name={route.name || null}
            data={route.data || null}
            render={route.render}
          />
        ))}
      </Switch>
    </HashRouter>
  </GlobalContext.Provider>
);

const container = document.getElementById('newsletters_container');
if (container) {
  // eslint-disable-next-line react/no-render-return-value
  window.mailpoet_listing = ReactDOM.render(<App />, container);
}
