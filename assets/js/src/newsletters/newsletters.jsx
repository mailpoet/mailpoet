import React from 'react';
import ReactDOM from 'react-dom';
import {
  HashRouter, Switch, Route, Redirect, useParams,
} from 'react-router-dom';
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
import EventsConditions from 'newsletters/automatic_emails/events_conditions.jsx';
import NewsletterListStandard from 'newsletters/listings/standard.jsx';
import NewsletterListWelcome from 'newsletters/listings/welcome.jsx';
import NewsletterListNotification from 'newsletters/listings/notification.jsx';
import NewsletterListNotificationHistory from 'newsletters/listings/notification_history.jsx';
import NewsletterSendingStatus from 'newsletters/sending_status.jsx';
import Listings from 'newsletters/automatic_emails/listings.jsx';
import CampaignStatsPage from 'newsletters/campaign_stats/page.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';
import RoutedTabs from 'common/tabs/routed_tabs';
import Tab from 'common/tabs/tab';
import withNpsPoll from 'nps_poll.jsx';
import ListingHeading from 'newsletters/listings/heading.jsx';
import ListingHeadingDisplay from 'newsletters/listings/heading_display.jsx';
import FeatureAnnouncement from 'announcements/feature_announcement.jsx';
import SubscribersLimitNotice from 'notices/subscribers_limit_notice.jsx';
import InvalidMssKeyNotice from 'notices/invalid_mss_key_notice';
import TransactionalEmailsProposeOptInNotice from 'notices/transactional_emails_propose_opt_in_notice';

const automaticEmails = window.mailpoet_woocommerce_automatic_emails || [];

const trackTabSwitch = (tabKey) => MailPoet.trackEvent(
  `Tab Emails > ${tabKey} clicked`,
  { 'MailPoet Free version': window.mailpoet_version },
);

const Tabs = withNpsPoll(() => {
  const { parentId } = useParams();
  return (
    <RoutedTabs
      activeKey="standard"
      routerType="switch-only"
      onSwitch={(tabKey) => trackTabSwitch(tabKey)}
      automationId="newsletters_listing_tabs"
    >
      <Tab
        key="standard"
        route="standard/(.*)?"
        title={MailPoet.I18n.t('tabStandardTitle')}
        automationId={`tab-${MailPoet.I18n.t('tabStandardTitle')}`}
      >
        <NewsletterListStandard />
      </Tab>
      <Tab
        key="welcome"
        route="welcome/(.*)?"
        title={MailPoet.I18n.t('tabWelcomeTitle')}
        automationId={`tab-${MailPoet.I18n.t('tabWelcomeTitle')}`}
      >
        <NewsletterListWelcome />
      </Tab>
      <Tab
        key="notification"
        route="notification/(.*)?"
        title={MailPoet.I18n.t('tabNotificationTitle')}
        automationId={`tab-${MailPoet.I18n.t('tabNotificationTitle')}`}
      >
        {
          parentId
            ? <NewsletterListNotificationHistory />
            : <NewsletterListNotification />
        }
      </Tab>
      {window.mailpoet_woocommerce_active && _.map(automaticEmails, (email) => (
        <Tab
          key={email.slug}
          route={`${email.slug}/(.*)?`}
          title={email.title}
          automationId={`tab-${email.title}`}
        >
          <Listings />
        </Tab>
      ))}
    </RoutedTabs>
  );
});

const getAutomaticEmailsRoutes = () => {
  const routes = [];
  _.each(automaticEmails, (email) => {
    routes.push({
      path: `/${email.slug}/(.*)?`,
      component: Tabs,
    });

    const { events } = email;
    if (_.isObject(events)) {
      _.each(events, (event) => {
        routes.push({
          path: `/new/${email.slug}/${event.slug}/conditions`,
          render: (props) => {
            const componentProps = {
              ...props,
              email,
              name: event.slug,
            };
            // eslint-disable-next-line react/jsx-props-no-spreading
            return (<EventsConditions {...componentProps} />);
          },
        });
      });
    }

    routes.push({
      path: `/new/${email.slug}`,
      render: (props) => {
        const componentProps = {
          ...props,
          email,
        };
        // eslint-disable-next-line react/jsx-props-no-spreading
        return (<AutomaticEmailEventsList {...componentProps} />);
      },
    });
  });
  return routes;
};

const routes = [
  ...getAutomaticEmailsRoutes(),

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
];

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <HashRouter>
      <Notices />

      <ListingHeadingDisplay>
        <ListingHeading />
        <FeatureAnnouncement hasNews={window.mailpoet_feature_announcement_has_news} />
      </ListingHeadingDisplay>

      <SubscribersLimitNotice />
      <TransactionalEmailsProposeOptInNotice
        mailpoetInstalledDaysAgo={window.mailpoet_installed_days_ago}
        sendTransactionalEmails={window.mailpoet_send_transactional_emails}
        mtaMethod={window.mailpoet_mta_method}
        apiVersion={window.mailpoet_api_version}
        noticeDismissed={window.mailpoet_transactional_emails_opt_in_notice_dismissed}
      />
      <InvalidMssKeyNotice
        mssKeyInvalid={window.mailpoet_mss_key_invalid}
        subscribersCount={window.mailpoet_subscribers_count}
      />

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
