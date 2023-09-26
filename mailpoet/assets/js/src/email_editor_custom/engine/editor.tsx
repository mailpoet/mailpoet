import { InterfaceSkeleton } from '@wordpress/interface';
import { StrictMode, createRoot } from '@wordpress/element';
import { registerCoreBlocks } from '@wordpress/block-library';
import { Popover, SlotFillProvider } from '@wordpress/components';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { Header } from './components/header';
import { BlockEditor } from './components/block-editor';

function Editor() {
  return (
    <StrictMode>
      <ShortcutProvider>
        <SlotFillProvider>
          <InterfaceSkeleton header={<Header />} content={<BlockEditor />} />
          <Popover.Slot />
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
  registerCoreBlocks();
  const root = createRoot(container);
  root.render(<Editor />);
}
