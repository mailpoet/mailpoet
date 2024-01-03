import { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { TopBarWithBeamer } from 'common/top-bar/top-bar';
import { Popover, SlotFillProvider } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { registerTranslations } from 'common';
import { initializeApi } from './api';
import { legacyAutomationCount } from './config';
import { createStore, storeName } from './listing/store';
import { AutomationListing, AutomationListingHeader } from './listing';
import { registerApiErrorHandler } from './listing/api-error-handler';
import { Notices } from './listing/components/notices';
import { BuildYourOwnSection, HeroSection, TemplatesSection } from './sections';
import { MailPoet } from '../mailpoet';
import { LegacyAutomationsNotice } from './listing/legacy-automations-notice';

const trackOpenEvent = () => {
  MailPoet.trackEvent('Automations > Listing viewed');
};

function Content(): JSX.Element {
  const [isBooting, setIsBooting] = useState(true);
  const count = useSelect((select) => select(storeName).getAutomationCount());

  useEffect(() => {
    if (!isBooting || count === 0) {
      return;
    }
    trackOpenEvent();
    setIsBooting(false);
  }, [isBooting, count]);
  const content =
    count > 0 ? (
      <>
        <AutomationListingHeader />
        {legacyAutomationCount > 0 && <LegacyAutomationsNotice />}
        <AutomationListing />
      </>
    ) : (
      <HeroSection />
    );

  // Hide notices on onboarding screen
  useEffect(() => {
    const onboardingClass = 'mailpoet-automation-is-onboarding';
    const element = document.querySelector('body');
    if (count === 0 && !element.classList.contains(onboardingClass)) {
      element.classList.add(onboardingClass);
    }
    if (count > 0 && element.classList.contains(onboardingClass)) {
      element.classList.remove(onboardingClass);
    }
  }, [count]);
  return (
    <>
      {content}
      <TemplatesSection />
      <BuildYourOwnSection />
    </>
  );
}

function Automations(): JSX.Element {
  return (
    <>
      <TopBarWithBeamer />
      <Notices />
      <Content />
    </>
  );
}

function App(): JSX.Element {
  return (
    <SlotFillProvider>
      <BrowserRouter>
        <Automations />
        <Popover.Slot />
      </BrowserRouter>
    </SlotFillProvider>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  createStore();

  const container = document.getElementById('mailpoet_automation');
  if (container) {
    registerTranslations();
    registerApiErrorHandler();
    initializeApi();
    const root = createRoot(container);
    root.render(<App />);
  }
});
