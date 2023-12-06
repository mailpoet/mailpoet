import { createRoot } from 'react-dom/client';
import {
  HashRouter,
  Redirect,
  Route,
  Switch,
  useParams,
} from 'react-router-dom';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

import { MailPoet } from 'mailpoet';
import { NewsletterTypes } from 'newsletters/types';
import { NewsletterTemplates } from 'newsletters/templates.jsx';
import { NewsletterSend } from 'newsletters/send';
import { Congratulate } from 'newsletters/send/congratulate/congratulate.jsx';
import { NewsletterTypeStandard } from 'newsletters/types/standard.jsx';
import { NewsletterNotification } from 'newsletters/types/notification/notification.jsx';
import { NewsletterWelcome } from 'newsletters/types/welcome/welcome.jsx';
import { NewsletterTypeReEngagement } from 'newsletters/types/re-engagement/re-engagement';
import { NewsletterListStandard } from 'newsletters/listings/standard.jsx';
import { NewsletterListNotification } from 'newsletters/listings/notification.jsx';
import { NewsletterListReEngagement } from 'newsletters/listings/re-engagement.jsx';
import { NewsletterListNotificationHistory } from 'newsletters/listings/notification-history.jsx';
import { SendingStatus } from 'newsletters/sending-status.jsx';
import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { RoutedTabs } from 'common/tabs/routed-tabs';
import { ErrorBoundary, registerTranslations, Tab, withBoundary } from 'common';
import { withNpsPoll } from 'nps-poll.jsx';
import { ListingHeading } from 'newsletters/listings/heading.jsx';
import { ListingHeadingDisplay } from 'newsletters/listings/heading-display.jsx';
import { TransactionalEmailsProposeOptInNotice } from 'notices/transactional-emails-propose-opt-in-notice';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { CampaignStatsPage } from './campaign-stats/page';
import { CorruptEmailNotice } from '../notices/corrupt-email-notice';

const trackTabSwitch = (tabKey) =>
  MailPoet.trackEvent(`Tab Emails > ${tabKey} clicked`);

const Tabs = withNpsPoll(() => {
  const { parentId } = useParams();
  return (
    <>
      <ListingHeadingDisplay>
        <ListingHeading />
      </ListingHeadingDisplay>
      {MailPoet.corrupt_newsletters.length > 0 && (
        <CorruptEmailNotice newsletters={MailPoet.corrupt_newsletters} />
      )}
      <RoutedTabs
        activeKey="standard"
        routerType="switch-only"
        onSwitch={(tabKey) => trackTabSwitch(tabKey)}
        automationId="newsletters_listing_tabs"
      >
        <Tab
          key="standard"
          route="standard/(.*)?"
          title={__('Newsletters', 'mailpoet')}
          automationId={`tab-${__('Newsletters', 'mailpoet')}`}
        >
          <NewsletterListStandard />
        </Tab>
        <Tab
          key="notification"
          route="notification/(.*)?"
          title={__('Post Notifications', 'mailpoet')}
          automationId={`tab-${__('Post Notifications', 'mailpoet')}`}
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
          title={__('Re-engagement Emails', 'mailpoet')}
          automationId={`tab-${__('Re-engagement Emails', 'mailpoet')}`}
        >
          <NewsletterListReEngagement />
        </Tab>
      </RoutedTabs>
    </>
  );
});
Tabs.displayName = 'NewsletterTabs';

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
          <TransactionalEmailsProposeOptInNotice
            mailpoetInstalledDaysAgo={MailPoet.installedDaysAgo}
            sendTransactionalEmails={MailPoet.transactionalEmailsEnabled}
            mtaMethod={MailPoet.mtaMethod}
            apiVersion={MailPoet.apiVersion}
            noticeDismissed={MailPoet.transactionalEmailsOptInNoticeDismissed}
          />
        </ErrorBoundary>
        <ErrorBoundary>
          <MssAccessNotices />
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
  registerTranslations();
  const root = createRoot(container);
  root.render(<App />);
}
