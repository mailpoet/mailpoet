import classnames from 'classnames';
import ReactDOM from 'react-dom';
import { Button, Icon, Popover, SlotFillProvider } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { wordpress } from '@wordpress/icons';
import {
  ComplementaryArea,
  InterfaceSkeleton,
  FullscreenMode,
} from '@wordpress/interface';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { Header } from './components/header';
import { InserterSidebar } from './components/inserter-sidebar';
import { KeyboardShortcuts } from './components/keyboard-shortcuts';
import { Sidebar } from './components/sidebar';
import { Workflow } from './components/workflow';
import { store, storeName } from './store';
import { initializeApi } from '../api';
import { initialize as initializeMailPoetIntegration } from '../integrations/mailpoet';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/layout/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/editor/index.js

function Editor(): JSX.Element {
  const {
    isFullscreenActive,
    isInserterOpened,
    isSidebarOpened,
    showIconLabels,
  } = useSelect(
    (select) => ({
      isFullscreenActive: select(store).isFeatureActive('fullscreenMode'),
      isInserterOpened: select(store).isInserterSidebarOpened(),
      isSidebarOpened: select(store).isSidebarOpened(),
      showIconLabels: select(store).isFeatureActive('showIconLabels'),
    }),
    [],
  );

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
          header={<Header />}
          content={<Workflow />}
          sidebar={<ComplementaryArea.Slot scope={storeName} />}
          secondarySidebar={isInserterOpened ? <InserterSidebar /> : null}
        />
        <Popover.Slot />
      </SlotFillProvider>
    </ShortcutProvider>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation_editor');
  if (root) {
    initializeApi();
    initializeMailPoetIntegration();
    ReactDOM.render(<Editor />, root);
  }
});
