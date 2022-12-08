import _ from 'underscore';
import ReactDOM from 'react-dom';
import {
  HashRouter,
  Redirect,
  Route,
  Switch,
  useParams,
} from 'react-router-dom';
import PropTypes from 'prop-types';

import { Listings } from 'newsletters/automatic_emails/listings.jsx';
import { MailPoet } from 'mailpoet';
import { NewsletterTypes } from 'newsletters/types';
import { NewsletterTemplates } from 'newsletters/templates.jsx';
import { NewsletterSend } from 'newsletters/send.jsx';
import { Congratulate } from 'newsletters/send/congratulate/congratulate.jsx';
import { NewsletterTypeStandard } from 'newsletters/types/standard.jsx';
import { NewsletterNotification } from 'newsletters/types/notification/notification.jsx';
import { NewsletterWelcome } from 'newsletters/types/welcome/welcome.jsx';
import { NewsletterTypeReEngagement } from 'newsletters/types/re_engagement/re_engagement';
import { AutomaticEmailEventsList } from 'newsletters/types/automatic_emails/events_list.jsx';
import { EventsConditions } from 'newsletters/automatic_emails/events_conditions.jsx';
import { NewsletterListStandard } from 'newsletters/listings/standard.jsx';
import { NewsletterListWelcome } from 'newsletters/listings/welcome.jsx';
import { NewsletterListNotification } from 'newsletters/listings/notification.jsx';
import { NewsletterListReEngagement } from 'newsletters/listings/re_engagement.jsx';
import { NewsletterListNotificationHistory } from 'newsletters/listings/notification_history.jsx';
import { SendingStatus } from 'newsletters/sending_status.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { Notices } from 'notices/notices.jsx';
import { RoutedTabs } from 'common/tabs/routed_tabs';
import { ErrorBoundary, Tab, withBoundary } from 'common';
import { withNpsPoll } from 'nps_poll.jsx';
import { ListingHeading } from 'newsletters/listings/heading.jsx';
import { ListingHeadingDisplay } from 'newsletters/listings/heading_display.jsx';
import { SubscribersLimitNotice } from 'notices/subscribers_limit_notice';
import { InvalidMssKeyNotice } from 'notices/invalid_mss_key_notice';
import { TransactionalEmailsProposeOptInNotice } from 'notices/transactional_emails_propose_opt_in_notice';
import { EmailVolumeLimitNotice } from 'notices/email_volume_limit_notice';
import { CampaignStatsPage } from './campaign_stats/page';

const automaticEmails = window.mailpoet_woocommerce_automatic_emails || [];

const trackTabSwitch = (tabKey) =>
  MailPoet.trackEvent(`Tab Emails > ${tabKey} clicked`);

const Tabs = withNpsPoll(() => {
  const { parentId } = useParams();
  return (
    <>
      <ListingHeadingDisplay>
        <ListingHeading />
      </ListingHeadingDisplay>

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
          {parentId ? (
            <NewsletterListNotificationHistory parentId={parentId} />
          ) : (
            <NewsletterListNotification />
          )}
        </Tab>
        <Tab
          key="re_engagement"
          route="re_engagement/(.*)?"
          title={MailPoet.I18n.t('tabReEngagementTitle')}
          automationId={`tab-${MailPoet.I18n.t('tabReEngagementTitle')}`}
        >
          <NewsletterListReEngagement />
        </Tab>
        {window.mailpoet_woocommerce_active &&
          _.map(automaticEmails, (email) => (
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
    </>
  );
});
Tabs.displayName = 'NewsletterTabs';

const getAutomaticEmailsRoutes = () => {
  const routes = [];
  _.each(automaticEmails, (email) => {
    routes.push({
      path: `/${email.slug}/(.*)?`,
      component: withBoundary(Tabs),
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
            return (
              <ErrorBoundary>
                <EventsConditions {...componentProps} />
              </ErrorBoundary>
            );
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
        return (
          <ErrorBoundary>
            <AutomaticEmailEventsList {...componentProps} />
          </ErrorBoundary>
        );
      },
    });
  });
  return routes;
};

function NewNewsletter({ history }) {
  return (
    <ErrorBoundary>
      <NewsletterTypes
        history={history}
        hideClosingButton={window.mailpoet_newsletters_count === 0}
      />
    </ErrorBoundary>
  );
}

NewNewsletter.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

NewNewsletter.displayName = 'NewNewsletter';

const routes = [
  ...getAutomaticEmailsRoutes(),

  /* Listings */
  {
    path: '/notification/history/:parentId/(.*)?',
    render: withBoundary(Tabs),
  },
  {
    path: '/(standard|welcome|notification|re_engagement)/(.*)?',
    render: withBoundary(Tabs),
  },
  /* New newsletter: types */
  {
    path: '/new/standard',
    render: withBoundary(NewsletterTypeStandard),
  },
  {
    path: '/new/notification',
    render: withBoundary(NewsletterNotification),
  },
  {
    path: '/new/welcome',
    render: withBoundary(NewsletterWelcome),
  },
  {
    path: '/new/re-engagement',
    render: withBoundary(NewsletterTypeReEngagement),
  },
  /* Newsletter: type selection */
  {
    path: '/new',
    render: withBoundary(NewNewsletter),
  },
  /* Template selection */
  {
    name: 'template',
    path: '/template/:id',
    render: withBoundary(NewsletterTemplates),
  },
  /* congratulate */
  {
    path: '/send/congratulate/:id',
    render: withBoundary(Congratulate),
  },
  /* Sending options */
  {
    path: '/send/:id',
    render: withBoundary(NewsletterSend),
  },
  {
    path: '/sending-status/:id/(.*)?',
    render: withBoundary(SendingStatus),
  },
  {
    path: '/stats/:id/(.*)?',
    render: withBoundary(CampaignStatsPage),
  },
];

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <Notices />
        <ErrorBoundary>
          <SubscribersLimitNotice />
        </ErrorBoundary>
        <ErrorBoundary>
          <EmailVolumeLimitNotice />
        </ErrorBoundary>
        <ErrorBoundary>
          <TransactionalEmailsProposeOptInNotice
            mailpoetInstalledDaysAgo={MailPoet.installedDaysAgo}
            sendTransactionalEmails={MailPoet.transactionalEmailsEnabled}
            mtaMethod={MailPoet.mtaMethod}
            apiVersion={MailPoet.apiVersion}
            noticeDismissed={MailPoet.transactionalEmailsOptInNoticeDismissed}
          />
        </ErrorBoundary>
        <ErrorBoundary>
          <InvalidMssKeyNotice
            mssKeyInvalid={MailPoet.hasInvalidMssApiKey}
            subscribersCount={MailPoet.subscribersCount}
          />
        </ErrorBoundary>
        <Switch>
          <Route
            exact
            path="/"
            render={() => (
              <Redirect
                to={
                  window.mailpoet_newsletters_count === 0 ? '/new' : '/standard'
                }
              />
            )}
          />
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
}

const container = document.getElementById('newsletters_container');
if (container) {
  // eslint-disable-next-line react/no-render-return-value
  window.mailpoet_listing = ReactDOM.render(<App />, container);
}
