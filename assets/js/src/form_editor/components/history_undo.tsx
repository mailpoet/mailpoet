import React from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { undo as undoIcon } from '@wordpress/icons';

function HistoryUndo(props) {
  const hasUndo = useSelect(
    (select) => select('mailpoet-form-editor').hasEditorUndo(),
    []
  );
  const { historyMove } = useDispatch('mailpoet-form-editor');
  const undoAction = () => {
    historyMove('undo');
  };
  return (
    <Button
      {...props}
      icon={undoIcon}
      label={__('Undo')}
      aria-disabled={!hasUndo}
      onClick={hasUndo ? undoAction : undefined}
      className="editor-history__undo"
    />
  );
}

export default HistoryUndo;
