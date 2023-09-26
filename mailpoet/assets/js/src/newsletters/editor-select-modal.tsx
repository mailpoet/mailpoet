import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

type EditorSelectModalProps = {
  legacyEditorCallback: () => void;
  onClose: () => void;
  isModalOpen: boolean;
};

export function EditorSelectModal({
  legacyEditorCallback,
  isModalOpen,
  onClose,
}: EditorSelectModalProps) {
  if (!isModalOpen) {
    return null;
  }
  return (
    <Modal
      title={__('Choose an email editor', 'mailpoet')}
      onRequestClose={onClose}
    >
      <p>{__('Which editor do you want to use?', 'mailpoet')}</p>
      <p>
        <Button
          type="button"
          variant="primary"
          onClick={() => {
            legacyEditorCallback();
            onClose();
          }}
        >
          {__('Legacy editor', 'mailpoet')}
        </Button>
      </p>
      <p>
        <Button
          type="button"
          variant="primary"
          onClick={() => {
            window.location.href = 'post-new.php?post_type=mailpoet_email';
          }}
        >
          {__('Gutenberg Editor', 'mailpoet')}
        </Button>
      </p>
    </Modal>
  );
}
