import classnames from 'classnames';
import ReactDOM from 'react-dom';
import { useEffect, useState } from 'react';
import { Button, Icon, Popover, SlotFillProvider } from '@wordpress/components';
import { store as noticesStore } from '@wordpress/notices';
import {
  dispatch,
  select as globalSelect,
  StoreDescriptor,
  useSelect,
} from '@wordpress/data';
import { wordpress } from '@wordpress/icons';
import {
  ComplementaryArea,
  FullscreenMode,
  InterfaceSkeleton,
} from '@wordpress/interface';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { Header } from './components/header';
import { InserterSidebar } from './components/inserter-sidebar';
import { KeyboardShortcuts } from './components/keyboard-shortcuts';
import { EditorNotices } from './components/notices';
import { Sidebar } from './components/sidebar';
import { Automation } from './components/automation';
import { createStore, store } from './store';
import { initializeApi } from '../api';
import { initialize as initializeCoreIntegration } from '../integrations/core';
import { initialize as initializeMailPoetIntegration } from '../integrations/mailpoet';
import { MailPoet } from '../../mailpoet';
import { LISTING_NOTICE_PARAMETERS } from '../listing/automation-listing-notices';
import { registerApiErrorHandler } from './api-error-handler';
import { ActivatePanel } from './components/panel/activate-panel';
import { registerTranslations } from '../i18n';
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
  const automation = globalSelect(store).getAutomationData();
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
  const { createNotice } = dispatch(noticesStore as StoreDescriptor);
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
  if (!globalSelect(store).getAutomationSaved()) {
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
      isFullscreenActive: select(store).isFeatureActive('fullscreenMode'),
      isInserterOpened: select(store).isInserterSidebarOpened(),
      isSidebarOpened: select(store).isSidebarOpened(),
      isActivationPanelOpened: select(store).isActivationPanelOpened(),
      showIconLabels: select(store).isFeatureActive('showIconLabels'),
      automation: select(store).getAutomationData(),
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
              <Automation />
            </>
          }
          sidebar={<ComplementaryArea.Slot scope={store} />}
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
  createStore();

  const root = document.getElementById('mailpoet_automation_editor');
  if (root) {
    registerTranslations();
    registerApiErrorHandler();
    initializeApi();
    initializeCoreIntegration();
    initializeMailPoetIntegration();
    ReactDOM.render(<Editor />, root);
  }
});
