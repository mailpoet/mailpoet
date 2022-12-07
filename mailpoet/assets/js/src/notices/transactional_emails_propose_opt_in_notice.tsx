import { useState } from 'react';
import { Notice } from 'notices/notice';
import { MailPoet } from 'mailpoet';

type Props = {
  mailpoetInstalledDaysAgo: number;
  sendTransactionalEmails: boolean;
  noticeDismissed: string;
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
  if (noticeDismissed === '1') return null;
  if (hidden) return null;

  return (
    <Notice type="success" timeout={false} onClose={saveNoticeDismissed}>
      <h3>{MailPoet.I18n.t('transactionalEmailNoticeTitle')}</h3>
      <p>
        {MailPoet.I18n.t('transactionalEmailNoticeBody')}{' '}
        <a
          href="https://kb.mailpoet.com/article/292-choose-how-to-send-your-wordpress-websites-emails"
          target="_blank"
          rel="noopener noreferrer"
        >
          {MailPoet.I18n.t('transactionalEmailNoticeBodyReadMore')}
        </a>
      </p>
      <p>
        <button type="button" className="button" onClick={enable}>
          {MailPoet.I18n.t('transactionalEmailNoticeCTA')}
        </button>
      </p>
    </Notice>
  );
}

TransactionalEmailsProposeOptInNotice.displayName =
  'TransactionalEmailsProposeOptInNotice';
export { TransactionalEmailsProposeOptInNotice };
