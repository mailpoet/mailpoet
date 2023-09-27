import classnames from 'classnames';
import { useSelect } from '@wordpress/data';
import { InterfaceSkeleton, ComplementaryArea } from '@wordpress/interface';
import { StrictMode, createRoot } from '@wordpress/element';
import { registerCoreBlocks } from '@wordpress/block-library';
import { Popover, SlotFillProvider } from '@wordpress/components';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { EntityProvider } from '@wordpress/core-data';
import { Header } from './components/header';
import { BlockEditor } from './components/block-editor';
import { Sidebar } from './components/sidebar/sidebar';
import { InserterSidebar } from './components/inserter-sidebar/inserter-sidebar';
import { ListviewSidebar } from './components/listview-sidebar/listview-sidebar';
import { createStore, storeName } from './store';

function Editor() {
  const {
    isSidebarOpened,
    isInserterSidebarOpened,
    isListviewSidebarOpened,
    postId,
  } = useSelect(
    (select) => ({
      isSidebarOpened: select(storeName).isSidebarOpened(),
      isInserterSidebarOpened: select(storeName).isInserterSidebarOpened(),
      isListviewSidebarOpened: select(storeName).isListviewSidebarOpened(),
      postId: select(storeName).getEmailPostId(),
    }),
    [],
  );

  const className = classnames('interface-interface-skeleton', {
    'is-sidebar-opened': isSidebarOpened,
  });

  return (
    <StrictMode>
      <ShortcutProvider>
        <SlotFillProvider>
          <EntityProvider kind="postType" type="mailpoet_email" id={postId}>
            <Sidebar />
            <InterfaceSkeleton
              className={className}
              header={<Header />}
              content={<BlockEditor />}
              sidebar={<ComplementaryArea.Slot scope={storeName} />}
              secondarySidebar={
                (isInserterSidebarOpened && <InserterSidebar />) ||
                (isListviewSidebarOpened && <ListviewSidebar />)
              }
            />
            <Popover.Slot />
          </EntityProvider>
        </SlotFillProvider>
      </ShortcutProvider>
    </StrictMode>
  );
}

export function initialize(elementId: string) {
  createStore();
  const container = document.getElementById(elementId);
  if (!container) {
    return;
  }
  registerCoreBlocks();
  const root = createRoot(container);
  root.render(<Editor />);
}
