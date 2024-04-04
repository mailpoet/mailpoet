import '@wordpress/format-library'; // Enables text formatting capabilities
import { useSelect } from '@wordpress/data';
import { StrictMode, createRoot } from '@wordpress/element';
import { SlotFillProvider } from '@wordpress/components';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { withNpsPoll } from '../../nps-poll';
import { initBlocks } from './blocks';
import { initializeLayout } from './layouts/flex-email';
import InnerEditor from './components/block-editor/editor';
import { createStore, storeName } from './store';
import { initHooks } from './editor-hooks';
import { KeyboardShortcuts } from './components/keybord-shortcuts';

function Editor() {
  const { postId, settings } = useSelect(
    (select) => ({
      postId: select(storeName).getEmailPostId(),
      settings: select(storeName).getInitialEditorSettings(),
    }),
    [],
  );

  return (
    <StrictMode>
      <ShortcutProvider>
        <SlotFillProvider>
          <KeyboardShortcuts />
          <InnerEditor
            initialEdits={[]}
            postId={postId}
            postType="mailpoet_email"
            settings={settings}
          />
        </SlotFillProvider>
      </ShortcutProvider>
    </StrictMode>
  );
}

const EditorWithPool = withNpsPoll(Editor);

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
  root.render(<EditorWithPool />);
}
