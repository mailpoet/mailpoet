import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as noticesStore } from '@wordpress/notices';

export function TrashModal({
  onClose,
  onRemove,
  postId,
}: {
  onClose: () => void;
  onRemove: () => void;
  postId: number;
}) {
  const { getLastEntityDeleteError } = useSelect(coreStore);
  const { deleteEntityRecord } = useDispatch(coreStore);
  const { createErrorNotice } = useDispatch(noticesStore);
  const closeCallback = () => {
    onClose();
  };
  const trashCallback = async () => {
    const success = await deleteEntityRecord(
      'postType',
      'mailpoet_email',
      postId as unknown as string,
      {},
      { throwOnError: false },
    );
    if (success) {
      onRemove();
    } else {
      const lastError = getLastEntityDeleteError(
        'postType',
        'mailpoet_email',
        postId,
      );
      // Already deleted.
      if (lastError?.code === 410) {
        onRemove();
      } else {
        const errorMessage = lastError?.message
          ? (lastError.message as string)
          : __(
              'An error occurred while moving the email to the trash.',
              'mailpoet',
            );
        await createErrorNotice(errorMessage, {
          type: 'snackbar',
          isDismissible: true,
          context: 'email-editor',
        });
      }
    }
  };
  return (
    <Modal
      className="mailpoet-move-to-trash-modal"
      title={__('Move to trash', 'mailpoet')}
      onRequestClose={closeCallback}
      focusOnMount="firstContentElement"
    >
      <p>
        {__('Are you sure you want to move this email to trash?', 'mailpoet')}
      </p>
      <div className="mailpoet-send-preview-modal-footer">
        <Button variant="tertiary" onClick={closeCallback}>
          {__('Cancel', 'mailpoet')}
        </Button>
        <Button variant="primary" onClick={trashCallback}>
          {__('Move to trash', 'mailpoet')}
        </Button>
      </div>
    </Modal>
  );
}

export default TrashModal;
