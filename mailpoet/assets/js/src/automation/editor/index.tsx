import classnames from 'classnames';
import ReactDOM from 'react-dom';
import { useEffect, useState } from 'react';
import { Button, Icon, Popover, SlotFillProvider } from '@wordpress/components';
import { store as noticesStore } from '@wordpress/notices';
import { dispatch, select as globalSelect, useSelect } from '@wordpress/data';
import { getSettings, setSettings } from '@wordpress/date';
import { wordpress } from '@wordpress/icons';
import {
  ComplementaryArea,
  FullscreenMode,
  InterfaceSkeleton,
} from '@wordpress/interface';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { __, setLocaleData } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { registerTranslations } from 'common';
import { Header } from './components/header';
import { InserterSidebar } from './components/inserter-sidebar';
import { KeyboardShortcuts } from './components/keyboard-shortcuts';
import { EditorNotices } from './components/notices';
import { Sidebar } from './components/sidebar';
import { Automation } from './components/automation';
import { createStore, storeName } from './store';
import { initializeApi } from '../api';
import { initialize as initializeCoreIntegration } from '../integrations/core';
import { initialize as initializeMailPoetIntegration } from '../integrations/mailpoet';
import { initialize as initializeWooCommerceIntegration } from '../integrations/woocommerce';
import { MailPoet } from '../../mailpoet';
import { LISTING_NOTICE_PARAMETERS } from '../listing/automation-listing-notices';
import { registerApiErrorHandler } from './api-error-handler';
import { ActivatePanel } from './components/panel/activate-panel';
import { AutomationStatus } from '../listing/automation';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/layout/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/editor/index.js

// disable inserter sidebar until we implement drag & drop
const showInserterSidebar = false;

/**
 * Show temporary message that active automations cant be updated
 *
 * see MAILPOET-4744
 */
function updatingActiveAutomationNotPossible() {
  const automation = globalSelect(storeName).getAutomationData();
  if (
    ![AutomationStatus.ACTIVE, AutomationStatus.DEACTIVATING].includes(
      automation.status,
    )
  ) {
    return;
  }
  if (automation.stats.totals.in_progress === 0) {
    return;
  }
  const { createNotice } = dispatch(noticesStore);
  void createNotice(
    'success',
    __(
      'Editing an active automation is temporarily unavailable. We are working on introducing this functionality.',
      'mailpoet',
    ),
    {
      type: 'snackbar',
    },
  );
}

function onUnload(event) {
  if (!globalSelect(storeName).getAutomationSaved()) {
    // eslint-disable-next-line no-param-reassign
    event.returnValue = __(
      'There are unsaved changes that will be lost. Do you want to continue?',
      'mailpoet',
    );
    return event.returnValue;
  }
  return '';
}

function useConfirmUnsaved() {
  useEffect(() => {
    window.addEventListener('beforeunload', onUnload);
    return () => window.removeEventListener('beforeunload', onUnload);
  }, []);
}

function Editor(): JSX.Element {
  const {
    isFullscreenActive,
    isInserterOpened,
    isActivationPanelOpened,
    isSidebarOpened,
    showIconLabels,
    automation,
  } = useSelect(
    (select) => ({
      isFullscreenActive: select(storeName).isFeatureActive('fullscreenMode'),
      isInserterOpened: select(storeName).isInserterSidebarOpened(),
      isSidebarOpened: select(storeName).isSidebarOpened(),
      isActivationPanelOpened: select(storeName).isActivationPanelOpened(),
      showIconLabels: select(storeName).isFeatureActive('showIconLabels'),
      automation: select(storeName).getAutomationData(),
    }),
    [],
  );
  const [isBooting, setIsBooting] = useState(true);

  useConfirmUnsaved();

  useEffect(() => {
    if (!isBooting) {
      return;
    }
    updatingActiveAutomationNotPossible();
    setIsBooting(false);
  }, [isBooting]);
  const className = classnames('interface-interface-skeleton', {
    'is-sidebar-opened': isSidebarOpened,
    'show-icon-labels': showIconLabels,
  });

  if (automation.status === 'trash') {
    window.location.href = addQueryArgs(MailPoet.urls.automationListing, {
      [LISTING_NOTICE_PARAMETERS.automationHadBeenDeleted]: automation.id,
    });
    return null;
  }

  return (
    <ShortcutProvider>
      <SlotFillProvider>
        <FullscreenMode isActive={isFullscreenActive} />
        <KeyboardShortcuts />
        <Sidebar />
        <InterfaceSkeleton
          className={className}
          drawer={
            isFullscreenActive && (
              <div className="edit-site-navigation-toggle">
                <Button
                  className="edit-site-navigation-toggle__button has-icon"
                  href="admin.php?page=mailpoet-automation"
                >
                  <Icon size={36} icon={wordpress} />
                </Button>
              </div>
            )
          }
          header={<Header showInserterToggle={showInserterSidebar} />}
          content={
            <>
              <EditorNotices />
              <Automation context="edit" />
            </>
          }
          sidebar={<ComplementaryArea.Slot scope={storeName} />}
          secondarySidebar={
            showInserterSidebar && isInserterOpened ? <InserterSidebar /> : null
          }
        />
        {isActivationPanelOpened && <ActivatePanel />}
        <Popover.Slot />
      </SlotFillProvider>
    </ShortcutProvider>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  setLocaleData(window.wp.i18n.getLocaleData());

  if (window.wp.date.getSettings !== undefined) {
    const dateSettings = window.wp as unknown as {
      date: { getSettings: typeof getSettings };
    };
    setSettings(dateSettings.date.getSettings());
  } else {
    const dateSettings = window.wp as unknown as {
      /* eslint-disable no-underscore-dangle */
      date: { __experimentalGetSettings: typeof getSettings };
    };
    setSettings(dateSettings.date.__experimentalGetSettings());
  }

  createStore();

  const root = document.getElementById('mailpoet_automation_editor');
  if (root) {
    registerTranslations();
    registerApiErrorHandler();
    initializeApi();
    initializeCoreIntegration();
    initializeMailPoetIntegration();
    initializeWooCommerceIntegration();
    ReactDOM.render(<Editor />, root);
  }
});
