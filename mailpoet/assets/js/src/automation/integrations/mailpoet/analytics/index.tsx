import ReactDOM from 'react-dom';
import { BrowserRouter } from 'react-router-dom';
import { dispatch, select, useSelect } from '@wordpress/data';
import { TopBarWithBeamer } from '../../../../common/top-bar/top-bar';
import { Notices } from '../../../listing/components/notices';
import { Header } from './components/header';
import { Overview } from './components/overview';
import { Tabs } from './components/tabs';
import { createStore, Section, storeName } from './store';
import { createStore as editorStoreCreate } from '../../../editor/store';
import { registerApiErrorHandler } from '../../../listing/api-error-handler';
import { initializeApi } from './api';
import { initialize as initializeCoreIntegration } from '../../core';
import { initialize as initializeMailPoetIntegration } from '../index';
import { initialize as initializeWooCommerceIntegration } from '../../woocommerce';
import { PremiumModal } from '../../../../common/premium-modal';

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

function App(): JSX.Element {
  return (
    <BrowserRouter>
      <TopBarWithBeamer />
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
  initializeCoreIntegration();
  initializeMailPoetIntegration();
  initializeWooCommerceIntegration();
  registerApiErrorHandler();
  boot();
  ReactDOM.render(<App />, root);
});
