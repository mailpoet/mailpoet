import { __, _x } from '@wordpress/i18n';
import { useState } from 'react';
import { Notice } from 'notices/notice';
import { MailPoet } from 'mailpoet';

type Props = {
  mailpoetInstalledDaysAgo: number;
  sendTransactionalEmails: boolean;
  noticeDismissed: boolean;
  mtaMethod: string;
  apiVersion: string;
};

function TransactionalEmailsProposeOptInNotice({
  mailpoetInstalledDaysAgo,
  sendTransactionalEmails,
  mtaMethod,
  noticeDismissed,
  apiVersion,
}: Props) {
  const [hidden, setHidden] = useState(false);
  const saveNoticeDismissed = () => {
    void MailPoet.Ajax.post({
      api_version: apiVersion,
      endpoint: 'UserFlags',
      action: 'set',
      data: {
        transactional_emails_opt_in_notice_dismissed: '1',
      },
    });
  };
  const enable = () => {
    setHidden(true);
    void MailPoet.Ajax.post({
      api_version: apiVersion,
      endpoint: 'settings',
      action: 'set',
      data: {
        send_transactional_emails: '1',
      },
    });
    saveNoticeDismissed();
  };

  if (mailpoetInstalledDaysAgo < 30) return null;
  if (sendTransactionalEmails) return null;
  if (mtaMethod === 'PHPMail') return null;
  if (noticeDismissed) return null;
  if (hidden) return null;

  return (
    <Notice type="success" timeout={false} onClose={saveNoticeDismissed}>
      <h3>
        {__(
          'Good news! MailPoet can now send your website’s emails too',
          'mailpoet',
        )}
      </h3>
      <p>
        {__(
          'All of your WordPress and WooCommerce emails are sent with your hosting company, unless you have an SMTP plugin. Would you like such emails to be delivered with MailPoet’s active sending method for better deliverability?',
          'mailpoet',
        )}{' '}
        <a
          href="https://kb.mailpoet.com/article/292-choose-how-to-send-your-wordpress-websites-emails"
          target="_blank"
          rel="noopener noreferrer"
        >
          {
            // translators: This is a link that leads to more information about transactional emails
            __('Read more.', 'mailpoet')
          }
        </a>
      </p>
      <p>
        <button type="button" className="button" onClick={enable}>
          {
            // translators: Button, after clicking it we will enable transactional emails
            _x('Enable', 'verb', 'mailpoet')
          }
        </button>
      </p>
    </Notice>
  );
}

TransactionalEmailsProposeOptInNotice.displayName =
  'TransactionalEmailsProposeOptInNotice';
export { TransactionalEmailsProposeOptInNotice };
