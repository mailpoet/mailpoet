import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { check, cloud, Icon } from '@wordpress/icons';
import { storeName } from '../../store';

export function SaveButton() {
  const { saveEditedEmail } = useDispatch(storeName);

  const { hasEdits, isEmpty, isSaving } = useSelect(
    (select) => ({
      hasEdits: select(storeName).hasEdits(),
      isEmpty: select(storeName).isEmpty(),
      isSaving: select(storeName).isSaving(),
    }),
    [],
  );

  const isSaved = !isEmpty && !isSaving && !hasEdits;
  const isDisabled = isEmpty || isSaving || isSaved;

  let label = __('Save Draft', 'mailpoet');
  if (isSaved) {
    label = __('Saved', 'mailpoet');
  } else if (isSaving) {
    label = __('Saving', 'mailpoet');
  }

  return (
    <Button variant="tertiary" disabled={isDisabled} onClick={saveEditedEmail}>
      {isSaving && <Icon icon={cloud} />}
      {isSaved && <Icon icon={check} />}
      {label}
    </Button>
  );
}
