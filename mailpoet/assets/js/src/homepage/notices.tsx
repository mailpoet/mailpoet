import { MailPoet } from 'mailpoet';
import { Notices } from 'notices/notices';
import { SubscribersLimitNotice } from 'notices/subscribers_limit_notice';
import { EmailVolumeLimitNotice } from 'notices/email_volume_limit_notice';
import { InvalidMssKeyNotice } from 'notices/invalid_mss_key_notice';
import { TransactionalEmailsProposeOptInNotice } from 'notices/transactional_emails_propose_opt_in_notice';

export function HomepageNotices(): JSX.Element {
  return (
    <>
      <Notices />
      <SubscribersLimitNotice />
      <EmailVolumeLimitNotice />
      <InvalidMssKeyNotice
        mssKeyInvalid={MailPoet.hasInvalidMssApiKey}
        subscribersCount={MailPoet.subscribersCount}
      />
      <TransactionalEmailsProposeOptInNotice
        mailpoetInstalledDaysAgo={MailPoet.installedDaysAgo}
        sendTransactionalEmails={MailPoet.transactionalEmailsEnabled}
        mtaMethod={MailPoet.mtaMethod}
        apiVersion={MailPoet.apiVersion}
        noticeDismissed={MailPoet.transactionalEmailsOptInNoticeDismissed}
      />
    </>
  );
}
