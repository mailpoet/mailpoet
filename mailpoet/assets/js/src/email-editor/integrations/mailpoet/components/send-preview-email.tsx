import { Button, Modal, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ReactStringReplace from 'react-string-replace';
import { dispatch, useSelect } from '@wordpress/data';
import { storeName } from '../store';

type SendPreviewEmailProps = {
  isOpen: boolean;
  closeCallback: () => void;
  newsletterId: number | null;
};

export function SendPreviewEmail({
  isOpen,
  closeCallback,
  newsletterId,
}: SendPreviewEmailProps) {
  const { previewToEmail } = useSelect(
    (select) => ({
      previewToEmail: select(storeName).getPreviewToEmail(),
    }),
    [],
  );

  let description = ReactStringReplace(
    __(
      'Send yourself a test email to test how your email would look like in different email apps. You could also enter your [link1]Mail Tester[/link1] email below to test your spam score. [link2]Learn more[/link2].',
      'mailpoet',
    ),
    /\[link1\](.*?)\[\/link1\]/g,
    (match, i) => (
      <a
        key={i}
        href="https://www.mail-tester.com/"
        target="_blank"
        rel="noopener noreferrer"
      >
        {match}
      </a>
    ),
  );
  description = ReactStringReplace(
    description,
    /\[link2\](.*?)\[\/link2\]/g,
    (match, i) => (
      <a
        key={i}
        href="https://kb.mailpoet.com/article/147-test-your-spam-score-with-mail-tester"
        target="_blank"
        rel="noopener noreferrer"
      >
        {match}
      </a>
    ),
  );

  const handleSendPreviewEmail = () => {
    dispatch(storeName).requestSendingNewsletterPreview(
      newsletterId,
      previewToEmail,
    );
  };

  return (
    <>
      {isOpen ? (
        <Modal
          className="mailpoet-send-preview-email"
          title={__('Send a test email', 'mailpoet')}
          onRequestClose={closeCallback}
        >
          <p>{description}</p>
          <TextControl
            label={__('Send to', 'mailpoet')}
            onChange={(email) => {
              dispatch(storeName).updatePreviewToEmail(email);
            }}
            value={previewToEmail}
          />
          <div className="mailpoet-send-preview-modal-footer">
            <Button variant="tertiary" onClick={closeCallback}>
              {__('Close', 'mailpoet')}
            </Button>
            <Button variant="primary" onClick={handleSendPreviewEmail}>
              {__('Send test email', 'mailpoet')}
            </Button>
          </div>
        </Modal>
      ) : null}
    </>
  );
}
