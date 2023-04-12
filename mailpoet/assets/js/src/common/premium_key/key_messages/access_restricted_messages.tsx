import { __ } from '@wordpress/i18n';
import { useSelector } from 'settings/store/hooks';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { addMonths, startOfMonth } from 'date-fns';

function SubscribersLimitReachedMessage() {
  const upgradeLink = MailPoet.MailPoetComUrlFactory.getUpgradeUrl(
    MailPoet.pluginPartialKey,
  );
  return (
    <div className="mailpoet_error">
      {ReactStringReplace(
        __(
          'You are becoming a bigger sender – congratulations! To keep up with the growth, and continue sending, [link]please upgrade your plan[/link]',
          'mailpoet',
        ),
        /\[link](.*?)\[\/link]/g,
        (match) => (
          <a
            key={match}
            href={upgradeLink}
            className="mailpoet_error"
            target="_blank"
            rel="noopener noreferrer"
          >
            {match}
          </a>
        ),
      )}
    </div>
  );
}

function EmailVolumeLimitReachedMessage() {
  const upgradeLink = MailPoet.MailPoetComUrlFactory.getUpgradeUrl(
    MailPoet.pluginPartialKey,
  );
  const startOfNextMonth = startOfMonth(addMonths(new Date(), 1));
  const message = __(
    'You have sent more emails this month than your MailPoet plan includes, and sending has been temporarily paused. To continue sending with MailPoet Sending Service please [link]upgrade your plan[/link], or wait until sending is automatically resumed on %1$s',
    'mailpoet',
  ).replace('%1$s', MailPoet.Date.short(startOfNextMonth));

  return (
    <div className="mailpoet_error">
      {ReactStringReplace(message, /\[link](.*?)\[\/link]/g, (match) => (
        <a
          key={match}
          href={upgradeLink}
          className="mailpoet_error"
          target="_blank"
          rel="noopener noreferrer"
        >
          {match}
        </a>
      ))}
    </div>
  );
}

export function AccessRestrictedMessages() {
  const { mssAccessRestriction, premiumAccessRestriction } = useSelector(
    'getKeyActivationState',
  )();
  if (
    mssAccessRestriction === 'email_volume_limit_reached' ||
    premiumAccessRestriction === 'email_volume_limit_reached'
  ) {
    return <EmailVolumeLimitReachedMessage />;
  }
  if (
    mssAccessRestriction === 'subscribers_limit_reached' ||
    premiumAccessRestriction === 'subscribers_limit_reached'
  ) {
    return <SubscribersLimitReachedMessage />;
  }
  return null;
}
