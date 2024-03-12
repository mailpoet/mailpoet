import '@wordpress/format-library'; // Enables text formatting capabilities
import { useSelect } from '@wordpress/data';
import { StrictMode, createRoot } from '@wordpress/element';
import { SlotFillProvider } from '@wordpress/components';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { EntityProvider } from '@wordpress/core-data';
import { initBlocks } from './blocks';
import { initializeLayout } from './layouts/flex-email';
import { BlockEditor } from './components/block-editor';
import { createStore, storeName } from './store';
import { initHooks } from './hooks';
import { KeyboardShortcuts } from './components/keybord-shortcuts';
import './components/validation';

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
  initializeLayout();
  initBlocks();
  initHooks();
  const root = createRoot(container);
  root.render(<Editor />);
}
