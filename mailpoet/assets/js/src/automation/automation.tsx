import { useEffect, useState } from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter } from 'react-router-dom';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import { Popover, SlotFillProvider } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { initializeApi, useMutation } from './api';
import { registerTranslations } from './i18n';
import { createStore, storeName } from './listing/store';
import { AutomationListing, AutomationListingHeader } from './listing';
import { registerApiErrorHandler } from './listing/api-error-handler';
import { Notices } from './listing/components/notices';
import { AutomationListingNotices } from './listing/automation-listing-notices';
import { BuildYourOwnSection, HeroSection, TemplatesSection } from './sections';
import {
  CreateEmptyAutomationButton,
  CreateAutomationFromTemplateButton,
} from './testing';
import { MailPoet } from '../mailpoet';

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

function RecreateSchemaButton(): JSX.Element {
  const [createSchema, { loading, error }] = useMutation('system/database', {
    method: 'POST',
  });

  return (
    <div>
      <AutomationListingNotices />
      <button
        className="button button-link-delete"
        type="button"
        onClick={() => createSchema()}
        disabled={loading}
      >
        Recreate DB schema (data will be lost)
      </button>
      {error && (
        <div>{error?.data?.message ?? 'An unknown error occurred'}</div>
      )}
    </div>
  );
}

function DeleteSchemaButton(): JSX.Element {
  const [deleteSchema, { loading, error }] = useMutation('system/database', {
    method: 'DELETE',
  });

  return (
    <div>
      <button
        className="button button-link-delete"
        type="button"
        onClick={async () => {
          await deleteSchema();
          window.location.href =
            '/wp-admin/admin.php?page=mailpoet-experimental';
        }}
        disabled={loading}
      >
        Delete DB schema & deactivate feature
      </button>
      {error && (
        <div>{error?.data?.message ?? 'An unknown error occurred'}</div>
      )}
    </div>
  );
}

function App(): JSX.Element {
  return (
    <SlotFillProvider>
      <BrowserRouter>
        <div>
          <Automations />
          <div style={{ marginTop: 30, display: 'grid', gridGap: 8 }}>
            <CreateEmptyAutomationButton />
            <CreateAutomationFromTemplateButton slug="simple-welcome-email">
              Create testing automation from template (welcome email)
            </CreateAutomationFromTemplateButton>
            <CreateAutomationFromTemplateButton slug="welcome-email-sequence">
              Create testing automation from template (welcome sequence, only
              premium)
            </CreateAutomationFromTemplateButton>
            <CreateAutomationFromTemplateButton slug="advanced-welcome-email-sequence">
              Create testing automation from template (advanced welcome
              sequence, only premium)
            </CreateAutomationFromTemplateButton>
            <RecreateSchemaButton />
            <DeleteSchemaButton />
          </div>
          <Popover.Slot />
        </div>
      </BrowserRouter>
    </SlotFillProvider>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  createStore();

  const root = document.getElementById('mailpoet_automation');
  if (root) {
    registerTranslations();
    registerApiErrorHandler();
    initializeApi();
    ReactDOM.render(<App />, root);
  }
});
