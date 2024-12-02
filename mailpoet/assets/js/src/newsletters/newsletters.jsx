import { createRoot } from 'react-dom/client';
import {
  HashRouter,
  Navigate,
  Route,
  Routes,
  useNavigate,
  useLocation,
  useParams,
} from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { TabPanel } from '@wordpress/components';

import { MailPoet } from 'mailpoet';
import { NewsletterTypes } from 'newsletters/types';
import { NewsletterTemplates } from 'newsletters/templates.jsx';
import { NewsletterSend } from 'newsletters/send';
import { Congratulate } from 'newsletters/send/congratulate/congratulate.jsx';
import { NewsletterTypeStandard } from 'newsletters/types/standard.jsx';
import { NewsletterNotification } from 'newsletters/types/notification/notification.jsx';
import { NewsletterTypeReEngagement } from 'newsletters/types/re-engagement/re-engagement';
import { NewsletterListStandard } from 'newsletters/listings/standard.jsx';
import { NewsletterListNotification } from 'newsletters/listings/notification.jsx';
import { NewsletterListReEngagement } from 'newsletters/listings/re-engagement.jsx';
import { NewsletterListNotificationHistory } from 'newsletters/listings/notification-history.jsx';
import { SendingStatus } from 'newsletters/sending-status.jsx';
import { GlobalContext, useGlobalContextValue } from 'context';
import { GlobalNotices } from 'notices/global-notices';
import { Notices } from 'notices/notices.jsx';
import { ErrorBoundary, registerTranslations, withBoundary } from 'common';
import { withNpsPoll } from 'nps-poll.jsx';
import { ListingHeading } from 'newsletters/listings/heading.jsx';
import { ListingHeadingDisplay } from 'newsletters/listings/heading-display.jsx';
import { TransactionalEmailsProposeOptInNotice } from 'notices/transactional-emails-propose-opt-in-notice';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { CampaignStatsPage } from './campaign-stats/page';
import { CorruptEmailNotice } from '../notices/corrupt-email-notice';
import { LegacyAutomaticEmailsNotice } from '../notices/legacy-automatic-emails-notice';
import { TopBarWithBeamer } from '../common/top-bar/top-bar';
import { BackButton, PageHeader } from '../common/page-header';
import { ExampleApp } from './test-app';
import { createStore, newsletterStoreName } from './store';
import { useDispatch } from '@wordpress/data';
import { useEffect } from 'react';

const trackTabSwitch = (tabKey) =>
  MailPoet.trackEvent(`Tab Emails > ${tabKey} clicked`);

const Tabs = withNpsPoll(() => {
  const navigate = useNavigate();
  const location = useLocation();
  const { parentId } = useParams();

  const tabs = [
    {
      name: 'example',
      title: __('Newsletters', 'mailpoet'),
      className: 'data-automation-tab-newsletters',
      content: <ExampleApp />,
      automationId: 'tab-example',
    },
    {
      name: 'standard',
      title: __('Newsletters', 'mailpoet'),
      className: 'data-automation-tab-newsletters',
      content: <NewsletterListStandard />,
      automationId: 'tab-Newsletter',
    },
    {
      name: 'notification',
      title: __('Post Notifications', 'mailpoet'),
      className: 'data-automation-tab-post-notifications',
      content: parentId ? (
        <NewsletterListNotificationHistory parentId={parentId} />
      ) : (
        <NewsletterListNotification />
      ),
      automationId: 'tab-Post Notifications',
    },
    {
      name: 're_engagement',
      title: __('Re-engagement Emails', 'mailpoet'),
      className: 'data-automation-tab-re-engagement-emails',
      content: <NewsletterListReEngagement />,
      automationId: 'tab-Re-engagement Emails',
    },
  ];

  const currentTab = location.pathname.split('/')[1] || 'standard';

  return (
    <>
      <ListingHeadingDisplay>
        <TopBarWithBeamer />
      </ListingHeadingDisplay>
      {window.mailpoet_legacy_automatic_emails_count > 0 &&
        !window.mailpoet_legacy_automatic_emails_notice_dismissed && (
          <LegacyAutomaticEmailsNotice />
        )}
      {MailPoet.corrupt_newsletters.length > 0 && (
        <CorruptEmailNotice newsletters={MailPoet.corrupt_newsletters} />
      )}

      <ListingHeading />

      <div key="emails" data-automation-id="newsletters_listing_tabs">
        <TabPanel
          tabs={tabs}
          initialTabName={currentTab}
          onSelect={(tabName) => {
            if (currentTab !== tabName) {
              trackTabSwitch(tabName);
              navigate(`/${tabName}`);
            }
          }}
        >
          {(tab) => (
            <div key={tab.name} data-automation-id={tab.automationId}>
              {tab.content}
            </div>
          )}
        </TabPanel>
      </div>
    </>
  );
});
Tabs.displayName = 'NewsletterTabs';

function NewNewsletter() {
  return (
    <ErrorBoundary>
      <TopBarWithBeamer />
      <div className="mailpoet-main-container">
        <PageHeader
          heading={__('What would you like to create?', 'mailpoet')}
          headingPrefix={
            <BackButton
              href="#/"
              label={__('Listing', 'mailpoet')}
              aria-label={__('Go back to email listing page', 'mailpoet')}
            />
          }
        />
        <NewsletterTypes />
      </div>
    </ErrorBoundary>
  );
}

NewNewsletter.displayName = 'NewNewsletter';

const routes = [
  /* Listings */
  {
    path: '/notification/history/:parentId/*',
    children: withBoundary(Tabs),
  },
  {
    path: '/standard/*',
    children: withBoundary(Tabs),
  },
    path: '/example/*',
    children: withBoundary(Tabs),
  },
  {
    path: '/notification/*',
    children: withBoundary(Tabs),
  },
  {
    path: '/re_engagement/*',
    children: withBoundary(Tabs),
  },
  /* New newsletter: types */
  {
    path: '/new/standard',
    children: withBoundary(NewsletterTypeStandard),
  },
  {
    path: '/new/notification',
    children: withBoundary(NewsletterNotification),
  },
  {
    path: '/new/re-engagement',
    children: withBoundary(NewsletterTypeReEngagement),
  },
  /* Newsletter: type selection */
  {
    path: '/new',
    children: withBoundary(NewNewsletter),
  },
  /* Template selection */
  {
    name: 'template',
    path: '/template/:id',
    children: withBoundary(NewsletterTemplates),
  },
  /* congratulate */
  {
    path: '/send/congratulate/:id',
    children: withBoundary(Congratulate),
  },
  /* Sending options */
  {
    path: '/send/:id',
    children: withBoundary(NewsletterSend),
  },
  {
    path: '/sending-status/:id/*',
    children: withBoundary(SendingStatus),
  },
  {
    path: '/stats/:id/*',
    children: withBoundary(CampaignStatsPage),
  },
];

function App() {
  const dispatch = useDispatch();

  useEffect(() => {
    dispatch(newsletterStoreName).setNewsletterData("newsletters");
  }
  , []);

  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <GlobalNotices />
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
        <Routes>
          <Route
            exact
            path="/"
            element={
              <Navigate
                to={
                  window.mailpoet_newsletters_count === 0 ? '/new' : '/standard'
                }
              />
            }
          />
          {routes.map((route) => (
            <Route
              key={route.path}
              path={route.path}
              name={route.name || null}
              data={route.data || null}
              element={<route.children />}
            />
          ))}
        </Routes>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('newsletters_container');
if (container) {
  registerTranslations();
  createStore();
  const root = createRoot(container);
  root.render(<App />);
}
