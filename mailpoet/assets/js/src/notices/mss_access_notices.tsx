import { ErrorBoundary } from 'common';
import { MailPoet } from 'mailpoet';
import { SubscribersLimitNotice } from './subscribers_limit_notice';
import { EmailVolumeLimitNotice } from './email_volume_limit_notice';
import { InvalidMssKeyNotice } from './invalid_mss_key_notice';

export function MssAccessNotices() {
  return (
    <ErrorBoundary>
      {MailPoet.subscribersLimitReached && <SubscribersLimitNotice />}
      {MailPoet.emailVolumeLimitReached && <EmailVolumeLimitNotice />}
      {!MailPoet.subscribersLimitReached &&
        !MailPoet.emailVolumeLimitReached && (
          <InvalidMssKeyNotice
            mssKeyInvalid={MailPoet.hasInvalidMssApiKey}
            subscribersCount={MailPoet.subscribersCount}
          />
        )}
    </ErrorBoundary>
  );
}
