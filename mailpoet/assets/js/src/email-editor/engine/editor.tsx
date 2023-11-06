import '@wordpress/format-library'; // Enables text formatting capabilities
import { useSelect } from '@wordpress/data';
import { StrictMode, createRoot } from '@wordpress/element';
import { Popover, SlotFillProvider } from '@wordpress/components';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { EntityProvider } from '@wordpress/core-data';
import { initBlocks } from './blocks';
import { BlockEditor } from './components/block-editor';
import { createStore, storeName } from './store';
import { initHooks } from './hooks';
import { KeyboardShortcuts } from './components/keybord-shortcuts';

function Editor() {
  const { postId } = useSelect(
    (select) => ({
      postId: select(storeName).getEmailPostId(),
    }),
    [],
  );

  return (
    <StrictMode>
      <ShortcutProvider>
        <SlotFillProvider>
          <KeyboardShortcuts />
          <EntityProvider kind="postType" type="mailpoet_email" id={postId}>
            <BlockEditor />
            <Popover.Slot />
          </EntityProvider>
        </SlotFillProvider>
      </ShortcutProvider>
    </StrictMode>
  );
}

export function initialize(elementId: string) {
  const container = document.getElementById(elementId);
  if (!container) {
    return;
  }
  createStore();
  initBlocks();
  initHooks();
  const root = createRoot(container);
  root.render(<Editor />);
}
