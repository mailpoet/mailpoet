import React from 'react';
import Notice from 'notices/notice';
import MailPoet from 'mailpoet';

type Props = {
  mailpoetInstalledDaysAgo: number,
  sendTransactionalEmails: boolean,
  mtaMethod: string,
}

const TransactionalEmailsProposeOptInNotice = ({
  mailpoetInstalledDaysAgo,
  sendTransactionalEmails,
  mtaMethod,
}: Props) => {
  const enable = () => {};

  const onClose = () => {};

  if (mailpoetInstalledDaysAgo < 30) return null;
  if (sendTransactionalEmails) return null;
  if (mtaMethod === 'PHPMail') return null;

  return (
    <Notice type="success" timeout={false} onClose={onClose}>
      <h3>{MailPoet.I18n.t('transactionalEmailNoticeTitle')}</h3>
      <p>
        {MailPoet.I18n.t('transactionalEmailNoticeBody')}
        {' '}
        <a
          href="https://kb.mailpoet.com/article/292-choose-how-to-send-your-wordpress-websites-emails"
          target="_blank"
          rel="noopener noreferrer"
        >
          {MailPoet.I18n.t('transactionalEmailNoticeBodyReadMore')}
        </a>
      </p>
      <p>
        <button
          type="button"
          className="button"
          onClick={enable}
        >
          {MailPoet.I18n.t('transactionalEmailNoticeCTA')}
        </button>
      </p>
    </Notice>
  );
};

export default TransactionalEmailsProposeOptInNotice;
