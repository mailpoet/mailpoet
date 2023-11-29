import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { MailPoet } from '../mailpoet';

type EditorSelectModalProps = {
  onClose: () => void;
  isModalOpen: boolean;
};

export function EditorSelectModal({
  isModalOpen,
  onClose,
}: EditorSelectModalProps) {
  if (!isModalOpen) {
    return null;
  }
  return (
    <Modal
      title={__('New editor', 'mailpoet')}
      onRequestClose={onClose}
      className="mailpoet-new-editor-modal"
    >
      <div className="mailpoet-new-editor-modal-image">
        <span className="mailpoet-new-editor-modal-image__beta_label">
          {__('Beta version', 'mailpoet')}
        </span>
        <img
          src={`${MailPoet.cdnUrl}email-editor/new-editor-modal-header.png`}
          alt={__('New editor', 'mailpoet')}
          width="324"
          height="130"
        />
      </div>
      <p>
        {__(
          'Create modern, beautiful emails that embody your brand with advanced customization and editing capabilities.',
          'mailpoet',
        )}
      </p>
      <p className="mailpoet-new-editor-modal-note">
        {__(
          'Emails created in the new editor cannot be reverted to the legacy version.',
          'mailpoet',
        )}
      </p>
      <div className="mailpoet-new-editor-modal-footer">
        <Button
          type="button"
          variant="tertiary"
          onClick={() => {
            onClose();
          }}
        >
          {__('Cancel', 'mailpoet')}
        </Button>
        <Button
          type="button"
          variant="primary"
          onClick={() => {
            window.location.href = 'admin.php?page=mailpoet-email-editor';
          }}
        >
          {__('Continue', 'mailpoet')}
        </Button>
      </div>
    </Modal>
  );
}
