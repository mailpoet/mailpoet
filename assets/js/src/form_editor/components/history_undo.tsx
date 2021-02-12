import React, { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { undo as undoIcon } from '@wordpress/icons';
import { displayShortcut } from '@wordpress/keycodes';
import { useShortcut } from '@wordpress/keyboard-shortcuts';

function HistoryUndo(props: object): JSX.Element {
  const hasUndo = useSelect(
    (select) => select('mailpoet-form-editor').hasEditorUndo(),
    []
  );
  const { historyUndo } = useDispatch('mailpoet-form-editor');
  const { registerShortcut } = useDispatch('core/keyboard-shortcuts');

  const undoAction = (): void => {
    historyUndo();
  };

  useShortcut(
    // Shortcut name
    'mailpoet-form-editor/undo',
    // Shortcut callback
    (event): void => {
      historyUndo();
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

export default HistoryUndo;
