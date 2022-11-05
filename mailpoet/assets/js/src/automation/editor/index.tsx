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
import { Workflow } from './components/workflow';
import { createStore, storeName } from './store';
import { initializeApi } from '../api';
import { initialize as initializeCoreIntegration } from '../integrations/core';
import { initialize as initializeMailPoetIntegration } from '../integrations/mailpoet';
import { MailPoet } from '../../mailpoet';
import { LISTING_NOTICE_PARAMETERS } from '../listing/workflow-listing-notices';
import { registerApiErrorHandler } from './api-error-handler';
import { ActivatePanel } from './components/panel/activate-panel';
import { registerTranslations } from '../i18n';
import { WorkflowStatus } from '../listing/workflow';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/layout/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/editor/index.js

// disable inserter sidebar until we implement drag & drop
const showInserterSidebar = false;

/**
 * Show temporary message that active workflows cant be updated
 *
 * see MAILPOET-4744
 */
function updateingActiveWorkflowNotPossible() {
  const workflow = globalSelect(storeName).getWorkflowData();
  if (
    ![WorkflowStatus.ACTIVE, WorkflowStatus.DEACTIVATING].includes(
      workflow.status,
    )
  ) {
    return;
  }
  if (workflow.stats.totals.in_progress === 0) {
    return;
  }
  const { createNotice } = dispatch(noticesStore as StoreDescriptor);
  void createNotice(
    'success',
    __(
      'Editing an active workflow is temporarily unavailable. We are working on introducing this functionality.',
      'mailpoet',
    ),
    {
      type: 'snackbar',
    },
  );
}

function Editor(): JSX.Element {
  const {
    isFullscreenActive,
    isInserterOpened,
    isSidebarOpened,
    showIconLabels,
    workflow,
  } = useSelect(
    (select) => ({
      isFullscreenActive: select(storeName).isFeatureActive('fullscreenMode'),
      isInserterOpened: select(storeName).isInserterSidebarOpened(),
      isSidebarOpened: select(storeName).isSidebarOpened(),
      showIconLabels: select(storeName).isFeatureActive('showIconLabels'),
      workflow: select(storeName).getWorkflowData(),
    }),
    [],
  );
  const [showActivatePanel, setShowActivatePanel] = useState(false);
  const [isBooting, setIsBooting] = useState(true);

  useEffect(() => {
    if (!isBooting) {
      return;
    }
    updateingActiveWorkflowNotPossible();
    setIsBooting(false);
  }, [isBooting]);
  const className = classnames('interface-interface-skeleton', {
    'is-sidebar-opened': isSidebarOpened,
    'show-icon-labels': showIconLabels,
  });

  if (workflow.status === 'trash') {
    window.location.href = addQueryArgs(MailPoet.urls.automationListing, {
      [LISTING_NOTICE_PARAMETERS.workflowHadBeenDeleted]: workflow.id,
    });
    return null;
  }

  const toggleActivatePanel = () => {
    setShowActivatePanel(!showActivatePanel);
  };

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
          header={
            <Header
              showInserterToggle={showInserterSidebar}
              toggleActivatePanel={toggleActivatePanel}
            />
          }
          content={
            <>
              <EditorNotices />
              <Workflow />
            </>
          }
          sidebar={<ComplementaryArea.Slot scope={storeName} />}
          secondarySidebar={
            showInserterSidebar && isInserterOpened ? <InserterSidebar /> : null
          }
        />
        {showActivatePanel && <ActivatePanel onClose={toggleActivatePanel} />}
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
