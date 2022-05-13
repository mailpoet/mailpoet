import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import {
  useShortcut,
  store as keyboardShortcutsStore,
} from '@wordpress/keyboard-shortcuts';
import { __ } from '@wordpress/i18n';
import { stepSidebarKey, store, workflowSidebarKey } from '../../store';

// See: https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/keyboard-shortcuts/index.js

export function KeyboardShortcuts(): null {
  const { isSidebarOpened, selectedStep } = useSelect((select) => ({
    isSidebarOpened: select(store).isSidebarOpened,
    selectedStep: select(store).getSelectedStep,
  }));

  const { openSidebar, closeSidebar, toggleFeature } = useDispatch(store);

  const { registerShortcut } = useDispatch(keyboardShortcutsStore);

  useEffect(() => {
    registerShortcut({
      name: 'core/edit-post/toggle-fullscreen',
      category: 'global',
      description: __('Toggle fullscreen mode.'),
      keyCombination: {
        modifier: 'secondary',
        character: 'f',
      },
    });

    registerShortcut({
      name: 'core/edit-post/toggle-sidebar',
      category: 'global',
      description: __('Show or hide the settings sidebar.'),
      keyCombination: {
        modifier: 'primaryShift',
        character: ',',
      },
    });
  }, [registerShortcut]);

  useShortcut('core/edit-post/toggle-fullscreen', () => {
    toggleFeature('fullscreenMode');
  });

  useShortcut('core/edit-post/toggle-sidebar', (event) => {
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
