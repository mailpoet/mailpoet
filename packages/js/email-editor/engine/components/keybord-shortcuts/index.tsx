import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import {
  useShortcut,
  store as keyboardShortcutsStore,
} from '@wordpress/keyboard-shortcuts';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../store';

// See:
//    https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/keyboard-shortcuts/index.js
//    https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/keyboard-shortcuts/index.js

export function KeyboardShortcuts(): null {
  const { isSidebarOpened, hasEdits, isSaving } = useSelect((select) => ({
    isSidebarOpened: select(storeName).isSidebarOpened(),
    isSaving: select(storeName).isSaving(),
    hasEdits: select(storeName).hasEdits(),
  }));

  const { openSidebar, closeSidebar, saveEditedEmail, toggleFeature } =
    useDispatch(storeName);

  const { registerShortcut } = useDispatch(keyboardShortcutsStore);

  useEffect(() => {
    void registerShortcut({
      name: 'mailpoet/email-editor/toggle-fullscreen',
      category: 'global',
      description: __('Toggle fullscreen mode.', 'mailpoet'),
      keyCombination: {
        modifier: 'secondary',
        character: 'f',
      },
    });

    void registerShortcut({
      name: 'mailpoet/email-editor/toggle-sidebar',
      category: 'global',
      description: __('Show or hide the settings sidebar.', 'mailpoet'),
      keyCombination: {
        modifier: 'primaryShift',
        character: ',',
      },
    });

    void registerShortcut({
      name: 'mailpoet/email-editor/save',
      category: 'global',
      description: __('Save your changes.', 'mailpoet'),
      keyCombination: {
        modifier: 'primary',
        character: 's',
      },
    });
  }, [registerShortcut]);

  useShortcut('mailpoet/email-editor/toggle-fullscreen', () => {
    void toggleFeature('fullscreenMode');
  });

  useShortcut('mailpoet/email-editor/toggle-sidebar', (event) => {
    event.preventDefault();

    if (isSidebarOpened) {
      void closeSidebar();
    } else {
      void openSidebar();
    }
  });

  useShortcut('mailpoet/email-editor/save', (event) => {
    event.preventDefault();
    if (!hasEdits || isSaving) {
      return;
    }
    void saveEditedEmail();
  });

  return null;
}
