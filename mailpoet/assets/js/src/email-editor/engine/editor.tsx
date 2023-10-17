import '@wordpress/format-library'; // Enables text formatting capabilities
import { useSelect } from '@wordpress/data';
import { StrictMode, createRoot } from '@wordpress/element';
import { registerCoreBlocks } from '@wordpress/block-library';
import { Popover, SlotFillProvider } from '@wordpress/components';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { EntityProvider } from '@wordpress/core-data';
import { Hooks } from '../../hooks';
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

/**
 * Disable nesting columns inside columns by using WP hooks
 */
function disableNestedColumns() {
  Hooks.addFilter(
    'blocks.registerBlockType',
    'mailpoet-email-editor/change-columns-allowed-nesting',
    (settings, name) => {
      if (name === 'core/column') {
        // eslint-disable-next-line @typescript-eslint/no-unsafe-return
        return {
          ...settings,
          attributes: {
            ...settings.attributes,
            allowedBlocks: {
              type: 'array',
              default: ['core/paragraph', 'core/heading'],
            },
          },
        };
      }

      // eslint-disable-next-line @typescript-eslint/no-unsafe-return
      return settings;
    },
  );
}

export function initialize(elementId: string) {
  const container = document.getElementById(elementId);
  if (!container) {
    return;
  }
  createStore();
  disableNestedColumns();
  registerCoreBlocks();
  initHooks();
  const root = createRoot(container);
  root.render(<Editor />);
}
