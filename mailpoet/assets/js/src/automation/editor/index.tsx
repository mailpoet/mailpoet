import classnames from 'classnames';
import ReactDOM from 'react-dom';
import { SlotFillProvider } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import {
  ComplementaryArea,
  InterfaceSkeleton,
  FullscreenMode,
} from '@wordpress/interface';
import { Header } from './components/header';
import { Sidebar } from './components/sidebar';
import { store, storeName } from './store';

// See: https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/layout/index.js

function Editor(): JSX.Element {
  const {
    isFullscreenActive,
    isSidebarOpened,
  } = useSelect(
    (select) => ({
      isFullscreenActive: select(store).isFeatureActive('fullscreenMode'),
      isSidebarOpened: select(store).isSidebarOpened(),
    }),
    [],
  );

  const className = classnames(
    'edit-post-layout',
    'interface-interface-skeleton',
    {
      'is-sidebar-opened': isSidebarOpened,
    },
  );

  return (
    <SlotFillProvider>
      <FullscreenMode isActive={isFullscreenActive} />
      <Sidebar />
      <InterfaceSkeleton
        className={className}
        header={<Header />}
        content={<div>Content</div>}
        sidebar={<ComplementaryArea.Slot scope={storeName} />}
        secondarySidebar={<div>Secondary sidebar</div>}
      />
    </SlotFillProvider>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation_editor');
  if (root) {
    ReactDOM.render(<Editor />, root);
  }
});
