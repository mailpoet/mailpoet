import { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { undo as undoIcon } from '@wordpress/icons';
import { displayShortcut } from '@wordpress/keycodes';
import { useShortcut } from '@wordpress/keyboard-shortcuts';

export function HistoryUndo(props: Record<string, unknown>): JSX.Element {
  const hasUndo = useSelect(
    (select) => select('mailpoet-form-editor').hasEditorUndo(),
    [],
  );
  const { historyUndo } = useDispatch('mailpoet-form-editor');
  const { registerShortcut } = useDispatch('core/keyboard-shortcuts');

  const undoAction = (): void => {
    void historyUndo();
  };

  useShortcut(
    // Shortcut name
    'mailpoet-form-editor/undo',
    // Shortcut callback
    (event): void => {
      void historyUndo();
      event.preventDefault();
    },
  );

  useEffect((): void => {
    registerShortcut({
      name: 'mailpoet-form-editor/undo',
      category: 'block',
      description: __('Undo your last changes.'),
      keyCombination: {
        modifier: 'primary',
        character: 'z',
      },
    });
  }, [registerShortcut]);

  return (
    <Button
      {...props}
      icon={undoIcon}
      label={__('Undo')}
      shortcut={displayShortcut.primary('z')}
      aria-disabled={!hasUndo}
      onClick={hasUndo ? undoAction : undefined}
      className="editor-history__undo"
    />
  );
}
