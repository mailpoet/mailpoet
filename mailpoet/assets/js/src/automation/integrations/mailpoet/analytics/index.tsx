import classNames from 'classnames';
import ReactDOM from 'react-dom';
import { BrowserRouter } from 'react-router-dom';
import { dispatch, select, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { TopBarWithBeamer } from '../../../../common/top-bar/top-bar';
import { Notices } from '../../../listing/components/notices';
import { Header } from './components/header';
import { Overview } from './components/overview';
import { Tabs } from './components/tabs';
import { createStore, Section, storeName } from './store';
import {
  createStore as editorStoreCreate,
  storeName as editorStoreName,
} from '../../../editor/store';
import { registerApiErrorHandler } from '../../../listing/api-error-handler';
import { initializeApi } from './api';
import { PremiumModal } from '../../../../common/premium-modal';
import { AutomationStatus } from '../../../listing/automation';
import { MailPoet } from '../../../../mailpoet';
import { initializeIntegrations } from '../../../editor/integrations';

function Analytics(): JSX.Element {
  const premiumModal = useSelect((s) => s(storeName).getPremiumModal());
  const { closePremiumModal } = dispatch(storeName);

  return (
    <div className="mailpoet-automation-analytics">
      <Header />
      <Overview />
      <Tabs />
      {premiumModal && (
        <PremiumModal
          onRequestClose={closePremiumModal}
          tracking={{
            utm_campaign: premiumModal.utmCampaign ?? 'automation_analytics',
          }}
        >
          {premiumModal.content}
        </PremiumModal>
      )}
    </div>
  );
}

function TopBarWithBreadcrumb(): JSX.Element {
  const { automation } = useSelect((s) => ({
    automation: s(editorStoreName).getAutomationData(),
  }));

  let status = __('Draft', 'mailpoet');
  let statusClass = '';
  if (automation.status === AutomationStatus.ACTIVE) {
    status = __('Active', 'mailpoet');
    statusClass = 'mailpoet-analytics-badge-success';
  }
  if (automation.status === AutomationStatus.DEACTIVATING) {
    status = __('Deactivating', 'mailpoet');
    statusClass = 'mailpoet-analytics-badge-warning';
  }

  return (
    <TopBarWithBeamer>
      <p className="mailpoet-automation-analytics-title">
        <a href={MailPoet.urls.automationListing}>
          {__('Automations', 'mailpoet')}
        </a>{' '}
        › <strong>{automation.name}</strong>
        <div className={classNames('mailpoet-analytics-badge', statusClass)}>
          <span className="mailpoet-analytics-badge-text">{status}</span>
        </div>
      </p>
    </TopBarWithBeamer>
  );
}

function App(): JSX.Element {
  return (
    <BrowserRouter>
      <TopBarWithBreadcrumb />
      <Notices />
      <Analytics />
    </BrowserRouter>
  );
}

function boot() {
  initializeApi();
  select(storeName)
    .getSections()
    .forEach((section: Section) => {
      dispatch(storeName).updateSection(section);
    });
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation_analytics');
  if (!root) {
    return;
  }
  createStore();
  editorStoreCreate();
  initializeIntegrations();
  registerApiErrorHandler();
  boot();
  ReactDOM.render(<App />, root);
});
