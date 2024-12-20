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
        window.location.href = MailPoet.getBlockEmailEditorUrl(
          response.data.wp_post_id as string,
        );
      })
      .fail((response) => {
        setIsLoading(false);
        onClose();
        if (response.errors.length > 0) {
          notices.apiError(response, { scroll: true });
        }
      });
  }, [notices, onClose]);

  if (!isModalOpen) {
    return null;
  }

  MailPoet.trackEvent(
    'New Email Editor > try new email editor modal opened',
    {},
    { send_immediately: true },
  );

  return (
    <Modal
      title={__('Try the new email editor', 'mailpoet')}
      onRequestClose={() => {
        MailPoet.trackEvent(
          'New Email Editor > try new email editor modal closed',
        );
        onClose();
      }}
      className="mailpoet-new-editor-modal"
    >
      <div className="mailpoet-new-editor-modal-image">
        <span className="mailpoet-new-editor-modal-image__beta_label">
          {__('Alpha version', 'mailpoet')}
        </span>
        <img
          src={`${MailPoet.cdnUrl}email-editor/new-editor-modal-header.png`}
          alt={__('Try the new email editor', 'mailpoet')}
          width="324"
          height="130"
        />
      </div>
      <p>
        {__(
          'Get a sneak peek of an early version of the upcoming email design experience and help shape its development.',
          'mailpoet',
        )}
      </p>
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
            MailPoet.trackEvent(
              'New Email Editor > try new email editor modal cancel button clicked',
              {},
              { send_immediately: true },
              onClose,
            );
          }}
        >
          {__('Cancel', 'mailpoet')}
        </Button>
        <Button
          type="button"
          variant="primary"
          isBusy={isLoading}
          onClick={() => {
            MailPoet.trackEvent(
              'New Email Editor > try new email editor modal create with new editor button clicked',
              {},
              { send_immediately: true },
              createNewsletterAndOpenEditor,
            );
          }}
        >
          {__('Continue', 'mailpoet')}
        </Button>
      </div>
    </Modal>
  );
}
