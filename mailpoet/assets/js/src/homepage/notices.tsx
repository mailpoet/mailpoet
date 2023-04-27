import { MailPoet } from 'mailpoet';
import { Notices } from 'notices/notices';
import { TransactionalEmailsProposeOptInNotice } from 'notices/transactional_emails_propose_opt_in_notice';
import { MailerError } from 'notices/mailer_error';
import { MssAccessNotices } from 'notices/mss_access_notices';

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
