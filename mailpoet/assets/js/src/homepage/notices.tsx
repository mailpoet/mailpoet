import { MailPoet } from 'mailpoet';
import { Notices } from 'notices/notices';
import { TransactionalEmailsProposeOptInNotice } from 'notices/transactional-emails-propose-opt-in-notice';
import { MailerError } from 'notices/mailer-error';
import { MssAccessNotices } from 'notices/mss-access-notices';

export function HomepageNotices(): JSX.Element {
  return (
    <>
      <Notices />
      <MssAccessNotices />
      <TransactionalEmailsProposeOptInNotice
        mailpoetInstalledDaysAgo={MailPoet.installedDaysAgo}
        sendTransactionalEmails={MailPoet.transactionalEmailsEnabled}
        mtaMethod={MailPoet.mtaMethod}
        apiVersion={MailPoet.apiVersion}
        noticeDismissed={MailPoet.transactionalEmailsOptInNoticeDismissed}
      />
      <MailerError
        mtaLog={MailPoet.mtaLog}
        mtaMethod={MailPoet.mtaMethod}
        isInline
      />
    </>
  );
}
