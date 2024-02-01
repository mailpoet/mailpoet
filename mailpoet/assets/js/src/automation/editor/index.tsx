import classnames from 'classnames';
import { createRoot } from 'react-dom/client';
import { useEffect, useState } from 'react';
import { Button, Icon, Popover, SlotFillProvider } from '@wordpress/components';
import { store as noticesStore } from '@wordpress/notices';
import { dispatch, select as globalSelect, useSelect } from '@wordpress/data';
import { getSettings, setSettings } from '@wordpress/date';
import { Platform } from '@wordpress/element';
import { wordpress } from '@wordpress/icons';
import {
  store as interfaceStore,
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
import { automationSidebarKey, createStore, storeName } from './store';
import { initializeApi } from '../api';
import { MailPoet } from '../../mailpoet';
import { LISTING_NOTICES } from '../listing/automation-listing-notices';
import { registerApiErrorHandler } from './api-error-handler';
import { ActivatePanel } from './components/panel/activate-panel';
import { AutomationStatus } from '../listing/automation';
import { initializeIntegrations } from './integrations';

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
  if (globalSelect(storeName).getSavedState() !== 'saved') {
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
    if (automation.status === 'trash') {
      window.location.href = addQueryArgs(MailPoet.urls.automationListing, {
        notice: LISTING_NOTICES.automationHadBeenDeleted,
        'notice-args': [automation.name],
      });
    }
    updatingActiveAutomationNotPossible();
    setIsBooting(false);
  }, [automation.name, automation.status, isBooting]);

  if (automation.status === 'trash') {
    return null;
  }

  const className = classnames('interface-interface-skeleton', {
    'is-sidebar-opened': isSidebarOpened,
    'show-icon-labels': showIconLabels,
  });

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

  // Sync default sidebar state to the interface store before starting rendering,
  // so that the layout is computed early enough to center the automation scroll.
  const sidebarActiveByDefault = Platform.select({
    web: true,
    native: false,
  });
  dispatch(interfaceStore).enableComplementaryArea(
    storeName,
    sidebarActiveByDefault ? automationSidebarKey : undefined,
  );

  const container = document.getElementById('mailpoet_automation_editor');
  if (container) {
    registerTranslations();
    registerApiErrorHandler();
    initializeApi();
    initializeIntegrations();
    const root = createRoot(container);
    root.render(<Editor />);
  }
});
