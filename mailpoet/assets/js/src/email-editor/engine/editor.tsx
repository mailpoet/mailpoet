import '@wordpress/format-library'; // Enables text formatting capabilities
import { useSelect } from '@wordpress/data';
import { StrictMode, createRoot } from '@wordpress/element';
import { registerCoreBlocks } from '@wordpress/block-library';
import { Popover, SlotFillProvider } from '@wordpress/components';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { EntityProvider } from '@wordpress/core-data';
import { BlockEditor } from './components/block-editor';
import { createStore, storeName } from './store';

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
  createStore();
  const container = document.getElementById(elementId);
  if (!container) {
    return;
  }
  registerCoreBlocks();
  const root = createRoot(container);
  root.render(<Editor />);
}
