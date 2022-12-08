import { MailPoet } from 'mailpoet';
import { Notices } from 'notices/notices';
import { SubscribersLimitNotice } from 'notices/subscribers_limit_notice';
import { EmailVolumeLimitNotice } from 'notices/email_volume_limit_notice';
import { InvalidMssKeyNotice } from 'notices/invalid_mss_key_notice';

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
    </>
  );
}
