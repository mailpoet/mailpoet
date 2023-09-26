import ReactStringReplace from 'react-string-replace';
import { __, _x } from '@wordpress/i18n';
import { Tooltip } from 'help-tooltip.jsx';

type Props = {
  subscribersInPlan: number | false;
  subscribersInPlanLimit: number | false;
};

export function SubscribersInPlan({
  subscribersInPlan,
  subscribersInPlanLimit,
}: Props) {
  if (subscribersInPlan === false) {
    return null;
  }

  const subscribersInPlanCount = subscribersInPlanLimit ? (
    <b key="subscribers_count">
      {_x('%1$d / %2$d', 'count / total subscribers', 'mailpoet')
        .replace('%1$d', subscribersInPlan.toLocaleString())
        .replace('%2$d', subscribersInPlanLimit.toLocaleString())}
    </b>
  ) : (
    <b key="subscribers_count">{subscribersInPlan}</b>
  );

  return (
    <div className="mailpoet-subscribers-in-plan">
      {ReactStringReplace(
        _x(
          '%s subscribers in your plan',
          'number of subscribers in a sending plan',
          'mailpoet',
        ),
        '%s',
        () => subscribersInPlanCount,
      )}{' '}
      <Tooltip
        tooltip={__(
          'This is the total of subscribed, unconfirmed and inactive subscribers we count when you are sending with MailPoet Sending Service. The count excludes unsubscribed and bounced (invalid) email addresses.',
          'mailpoet',
        )}
        place="right"
      />
      <span className="mailpoet-subscribers-in-plan-spacer"> </span>
    </div>
  );
}
