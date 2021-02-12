import React, { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { redo as redoIcon } from '@wordpress/icons';
import { displayShortcut } from '@wordpress/keycodes';
import { useShortcut } from '@wordpress/keyboard-shortcuts';

function HistoryRedo(props: object): JSX.Element {
  const hasRedo = useSelect(
    (select) => select('mailpoet-form-editor').hasEditorRedo(),
    []
  );
  const { historyRedo } = useDispatch('mailpoet-form-editor');
  const { registerShortcut } = useDispatch('core/keyboard-shortcuts');

  const redoAction = (): void => {
    historyRedo();
  };

  useShortcut(
    // Shortcut name
    'mailpoet-form-editor/redo',
    // Shortcut callback
    (event): void => {
      redoAction();
      event.preventDefault();
    },
  );

  useEffect((): void => {
    registerShortcut({
      name: 'mailpoet-form-editor/redo',
      category: 'block',
      description: __('Redo your last undo.'),
      keyCombination: {
        modifier: 'primaryShift',
        character: 'z',
      },
    });
  }, [registerShortcut]);

  return (
    <Button
      {...props}
      icon={redoIcon}
      label={__('Redo')}
      shortcut={displayShortcut.primaryShift('z')}
      aria-disabled={!hasRedo}
      onClick={hasRedo ? redoAction : undefined}
      className="editor-history__redo"
    />
  );
}

export default HistoryRedo;
