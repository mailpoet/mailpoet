import { ErrorBoundary } from 'common';
import { MailPoet } from 'mailpoet';
import { SubscribersLimitNotice } from './subscribers-limit-notice';
import { EmailVolumeLimitNotice } from './email-volume-limit-notice';
import { InvalidMssKeyNotice } from './invalid-mss-key-notice';

/**
 * This component handles notices for exceeded limits and invalid MSS key.
 * We display three types of notices:
 * - Subscribers limit reached notice
 * - Email volume limit reached notice
 * - Invalid MSS key notice
 *
 * Subscribers limit reached notice
 * ================================
 * This notice is displayed when the number of that plugin knows from the DB is higher than plan's limit or
 * the basic plugin limit (in case there is no API key).
 * The notice is also displayed when an API key check returns access_restriction 'subscribers_limit_reached'.
 *
 * Note: there might be also a subscriber limit notice displayed when mailer log contains subscribers_limit_reached error.
 * The second notice is handled in mailer_log.tsx and is hidden in case this notice for subscriber limit reached notice is displayed.
 *
 * Email volume limit reached notice
 * =================================
 * This notice is displayed when the number of sent emails reported by MSS key check API is higher than plan's limit.
 * The notice is also displayed when an API key check returns access_restriction 'emails_limit_reached'.
 *
 * Note: there might be also a email limits notice displayed when mailer log contains email_limit_reached error.
 * The second notice is handled in mailer_log.tsx and is hidden in case this notice for email limit reached notice is displayed.
 *
 * Invalid MSS key notice
 * =================================
 * This notice is displayed when the MSS key is invalid and MailPoet sending service is set as the sending method.
 * The notice is hidden when the MSS key has reached some of the limits.
 */

export function MssAccessNotices() {
  return (
    <ErrorBoundary>
      {MailPoet.subscribersLimitReached && <SubscribersLimitNotice />}
      {MailPoet.emailVolumeLimitReached && <EmailVolumeLimitNotice />}
      {!MailPoet.subscribersLimitReached &&
        !MailPoet.emailVolumeLimitReached && (
          <InvalidMssKeyNotice
            mssKeyInvalid={MailPoet.hasInvalidMssApiKey}
            pluginPartialKey={MailPoet.pluginPartialKey}
            premiumKeyValid={MailPoet.hasValidPremiumKey}
            subscribersCount={MailPoet.subscribersCount}
          />
        )}
    </ErrorBoundary>
  );
}
