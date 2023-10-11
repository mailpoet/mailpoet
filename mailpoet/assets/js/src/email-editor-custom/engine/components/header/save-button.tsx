import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from '../../store';

export function SaveButton() {
  const { saveEditedEmail } = useDispatch(storeName);

  const { hasEdits } = useSelect(
    (select) => ({
      hasEdits: select(storeName).hasEdits(),
    }),
    [],
  );

  return (
    <Button variant="link" disabled={!hasEdits} onClick={saveEditedEmail}>
      {__('Save Draft', 'mailpoet')}
    </Button>
  );
}
