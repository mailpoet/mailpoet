import React from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { redo as redoIcon } from '@wordpress/icons';

function HistoryRedo(props) {
  const hasRedo = useSelect(
    (select) => select('mailpoet-form-editor').hasEditorRedo(),
    []
  );
  const { historyRedo } = useDispatch('mailpoet-form-editor');
  const redoAction = () => {
    historyRedo();
  };
  return (
    <Button
      {...props}
      icon={redoIcon}
      label={__('Redo')}
      aria-disabled={!hasRedo}
      onClick={hasRedo ? redoAction : undefined}
      className="editor-history__redo"
    />
  );
}

export default HistoryRedo;
