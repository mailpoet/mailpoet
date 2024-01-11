import { useState, useContext, useCallback } from 'react';
import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { GlobalContext, GlobalContextValue } from 'context';
import { MailPoet } from '../mailpoet';

type EditorSelectModalProps = {
  onClose: () => void;
  isModalOpen: boolean;
};

export function EditorSelectModal({
  isModalOpen,
  onClose,
}: EditorSelectModalProps) {
  const [isLoading, setIsLoading] = useState(false);
  const { notices } = useContext<GlobalContextValue>(GlobalContext);

  const createNewsletterAndOpenEditor = useCallback(() => {
    setIsLoading(true);
    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'standard',
        subject: __('Subject', 'mailpoet'),
        new_editor: true,
      },
    })
      .done((response) => {
        window.location.href = `admin.php?page=mailpoet-email-editor&postId=${
          response.data.wp_post_id as number
        }`;
      })
      .fail((response) => {
        setIsLoading(false);
        onClose();
        if (response.errors.length > 0) {
          notices.error(
            response.errors.map((error) => (
              <p key={error.message}>{error.message}</p>
            )),
            { scroll: true },
          );
        }
      });
  }, [notices, onClose]);

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
          isBusy={isLoading}
          onClick={createNewsletterAndOpenEditor}
        >
          {__('Continue', 'mailpoet')}
        </Button>
      </div>
    </Modal>
  );
}
