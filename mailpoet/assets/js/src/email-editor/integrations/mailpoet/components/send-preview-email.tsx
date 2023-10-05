import { Button, Modal, TextControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { Icon, check } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import ReactStringReplace from 'react-string-replace';
import { SendingPreviewStatus, storeName } from '../store';

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
  const { previewToEmail, isSendingPreviewEmail, sendingPreviewStatus } =
    useSelect(
      (select) => ({
        previewToEmail: select(storeName).getPreviewToEmail(),
        isSendingPreviewEmail: select(storeName).getIsSendingPreviewEmail(),
        sendingPreviewStatus: select(storeName).getSendingPreviewStatus(),
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
    // We need to
    dispatch(storeName).setSendingPreviewStatus(null);
    dispatch(storeName).setIsSendingPreviewEmail(true);
    dispatch(storeName).requestSendingNewsletterPreview(
      newsletterId,
      previewToEmail,
      () => {
        dispatch(storeName).setIsSendingPreviewEmail(false);
      },
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
          {sendingPreviewStatus === SendingPreviewStatus.ERROR ? (
            <div className="mailpoet-send-preview-modal-notice-error">
              {__('Sorry, we were unable to send this email.', 'mailpoet')}
              <ul>
                <li>
                  {ReactStringReplace(
                    __(
                      'Please check your [link]sending method configuration[/link] with your hosting provider.',
                      'mailpoet',
                    ),
                    /\[link\](.*?)\[\/link\]/g,
                    (match) => (
                      <a
                        href="admin.php?page=mailpoet-settings#mta"
                        key="check-sending"
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        {match}
                      </a>
                    ),
                  )}
                </li>
                <li>
                  {ReactStringReplace(
                    __(
                      'Or, sign up for MailPoet Sending Service to easily send emails. [link]Sign up for free[/link]',
                      'mailpoet',
                    ),
                    /\[link\](.*?)\[\/link\]/g,
                    (match) => (
                      <a
                        href={new URL(
                          'free-plan',
                          'https://www.mailpoet.com/',
                        ).toString()}
                        key="sign-up-for-free"
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        {match}
                      </a>
                    ),
                  )}
                </li>
              </ul>
            </div>
          ) : null}
          <p>{description}</p>
          <TextControl
            label={__('Send to', 'mailpoet')}
            onChange={(email) => {
              dispatch(storeName).updatePreviewToEmail(email);
            }}
            value={previewToEmail}
          />
          {sendingPreviewStatus === SendingPreviewStatus.SUCCESS ? (
            <p className="mailpoet-send-preview-modal-notice-success">
              <Icon icon={check} style={{ fill: '#4AB866' }} />
              {__('Test email sent successfully!', 'mailpoet')}
            </p>
          ) : null}
          <div className="mailpoet-send-preview-modal-footer">
            <Button variant="tertiary" onClick={closeCallback}>
              {__('Close', 'mailpoet')}
            </Button>
            <Button variant="primary" onClick={handleSendPreviewEmail}>
              {isSendingPreviewEmail
                ? __('Sending...', 'mailpoet')
                : __('Send test email', 'mailpoet')}
            </Button>
          </div>
        </Modal>
      ) : null}
    </>
  );
}
