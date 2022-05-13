import classnames from 'classnames';
import ReactDOM from 'react-dom';
import { SlotFillProvider } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
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
import { store, storeName } from './store';

// See: https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/layout/index.js

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

  const className = classnames(
    'edit-post-layout',
    'interface-interface-skeleton',
    {
      'is-sidebar-opened': isSidebarOpened,
      'show-icon-labels': showIconLabels,
    },
  );

  return (
    <ShortcutProvider>
      <SlotFillProvider>
        <FullscreenMode isActive={isFullscreenActive} />
        <KeyboardShortcuts />
        <Sidebar />
        <InterfaceSkeleton
          className={className}
          header={<Header />}
          content={<div>Content</div>}
          sidebar={<ComplementaryArea.Slot scope={storeName} />}
          secondarySidebar={isInserterOpened ? <InserterSidebar /> : null}
        />
      </SlotFillProvider>
    </ShortcutProvider>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation_editor');
  if (root) {
    ReactDOM.render(<Editor />, root);
  }
});
