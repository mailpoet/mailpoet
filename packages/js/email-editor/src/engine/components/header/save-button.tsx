import { useRef } from '@wordpress/element';
import { Button, Dropdown } from '@wordpress/components';
import {
  // @ts-expect-error No types available for useEntitiesSavedStatesIsDirty
  useEntitiesSavedStatesIsDirty,
  // @ts-expect-error Our current version of packages doesn't have EntitiesSavedStates export
  EntitiesSavedStates,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { check, cloud, Icon } from '@wordpress/icons';
import { storeName } from '../../store';

export function SaveButton() {
  const { saveEditedEmail } = useDispatch(storeName);

  const { dirtyEntityRecords } = useEntitiesSavedStatesIsDirty();

  const { hasEdits, isEmpty, isSaving } = useSelect(
    (select) => ({
      hasEdits: select(storeName).hasEdits(),
      isEmpty: select(storeName).isEmpty(),
      isSaving: select(storeName).isSaving(),
    }),
    [],
  );

  const buttonRef = useRef(null);

  const hasNonEmailEdits = dirtyEntityRecords.some(
    (entity) => entity.name !== 'mailpoet_email',
  );

  const isSaved = !isEmpty && !isSaving && !hasEdits;
  const isDisabled = isEmpty || isSaving || isSaved;

  let label = __('Save Draft', 'mailpoet');
  if (isSaved) {
    label = __('Saved', 'mailpoet');
  } else if (isSaving) {
    label = __('Saving', 'mailpoet');
  }

  return hasNonEmailEdits ? (
    <div ref={buttonRef}>
      <Dropdown
        popoverProps={{
          placement: 'bottom',
          anchor: buttonRef.current,
        }}
        contentClassName="mailpoet-email-editor-save-button__dropdown"
        renderToggle={({ onToggle }) => (
          <Button onClick={onToggle} variant="tertiary">
            {hasEdits
              ? __('Save email & template', 'mailpoet')
              : __('Save template', 'mailpoet')}
          </Button>
        )}
        renderContent={({ onToggle }) => (
          <EntitiesSavedStates close={onToggle} />
        )}
      />
    </div>
  ) : (
    <Button variant="tertiary" disabled={isDisabled} onClick={saveEditedEmail}>
      {isSaving && <Icon icon={cloud} />}
      {isSaved && <Icon icon={check} />}
      {label}
    </Button>
  );
}
