import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import {
  useShortcut,
  store as keyboardShortcutsStore,
} from '@wordpress/keyboard-shortcuts';
import { __ } from '@wordpress/i18n';
import { stepSidebarKey, storeName, workflowSidebarKey } from '../../store';

// See:
//    https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/keyboard-shortcuts/index.js
//    https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/keyboard-shortcuts/index.js

export function KeyboardShortcuts(): null {
  const { isSidebarOpened, selectedStep } = useSelect((select) => ({
    isSidebarOpened: select(storeName).isSidebarOpened,
    selectedStep: select(storeName).getSelectedStep,
  }));

  const { openSidebar, closeSidebar, toggleFeature } = useDispatch(storeName);

  const { registerShortcut } = useDispatch(keyboardShortcutsStore);

  useEffect(() => {
    void registerShortcut({
      name: 'mailpoet/automation-editor/toggle-fullscreen',
      category: 'global',
      description: __('Toggle fullscreen mode.', 'mailpoet'),
      keyCombination: {
        modifier: 'secondary',
        character: 'f',
      },
    });

    void registerShortcut({
      name: 'mailpoet/automation-editor/toggle-sidebar',
      category: 'global',
      description: __('Show or hide the settings sidebar.', 'mailpoet'),
      keyCombination: {
        modifier: 'primaryShift',
        character: ',',
      },
    });
  }, [registerShortcut]);

  useShortcut('mailpoet/automation-editor/toggle-fullscreen', () => {
    toggleFeature('fullscreenMode');
  });

  useShortcut('mailpoet/automation-editor/toggle-sidebar', (event) => {
    event.preventDefault();

    if (isSidebarOpened()) {
      closeSidebar();
    } else {
      const sidebarToOpen = selectedStep()
        ? stepSidebarKey
        : workflowSidebarKey;
      openSidebar(sidebarToOpen);
    }
  });

  return null;
}
