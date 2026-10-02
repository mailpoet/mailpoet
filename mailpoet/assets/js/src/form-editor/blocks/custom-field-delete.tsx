import { useState } from 'react';
import { Button, Modal } from '@wordpress/components';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, _x } from '@wordpress/i18n';
import { storeName } from '../store/constants';

type Props = {
  isBusy?: boolean;
  onDelete?: () => void;
};

function CustomFieldDelete({
  isBusy = false,
  onDelete = () => {},
}: Props): JSX.Element | null {
  const [isConfirmOpen, setIsConfirmOpen] = useState(false);
  const canDelete = useSelect(
    (select) => select(storeName).canUserManageSubscribers(),
    [],
  );
  const selectedBlockClientId = useSelect(
    (select) => select(blockEditorStore).getSelectedBlockClientId(),
    [],
  );
  const { removeBlock } = useDispatch(blockEditorStore);

  if (!canDelete) {
    return null;
  }

  const title = _x(
    'Delete this custom field',
    'Text on the delete button',
    'mailpoet',
  );

  return (
    <>
      <Button
        isDestructive
        variant="link"
        isBusy={isBusy}
        onClick={() => setIsConfirmOpen(true)}
        className="button-on-top"
      >
        {title}
      </Button>
      {isConfirmOpen && (
        <Modal
          className="mailpoet-custom-field-delete-modal"
          title={title}
          onRequestClose={() => setIsConfirmOpen(false)}
        >
          <p>
            {__(
              'This permanently deletes the custom field and the values stored for all subscribers. It will also be removed from all forms and dynamic segments. This cannot be undone.',
              'mailpoet',
            )}
          </p>
          <p>
            {__(
              'To keep the field and its data, remove it only from this form.',
              'mailpoet',
            )}
          </p>
          <div className="mailpoet-custom-field-delete-modal__actions">
            <Button variant="tertiary" onClick={() => setIsConfirmOpen(false)}>
              {__('Cancel', 'mailpoet')}
            </Button>
            <Button
              variant="secondary"
              onClick={() => {
                setIsConfirmOpen(false);
                if (selectedBlockClientId) {
                  void removeBlock(selectedBlockClientId);
                }
              }}
            >
              {__('Remove from this form', 'mailpoet')}
            </Button>
            <Button
              variant="primary"
              isDestructive
              onClick={() => {
                setIsConfirmOpen(false);
                onDelete();
              }}
            >
              {__('Delete permanently', 'mailpoet')}
            </Button>
          </div>
        </Modal>
      )}
    </>
  );
}

export { CustomFieldDelete };
