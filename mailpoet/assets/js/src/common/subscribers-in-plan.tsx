import { __, _n, sprintf } from '@wordpress/i18n';
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

  return (
    <span className="mailpoet-segment-subscriber-count-message">
      <b>
        {subscribersInPlanLimit
          ? sprintf(
              // translators: %1$s is number of subscribers on the site and %2$s is the maximum number of subscribers the site can have on the current plan.
              _n(
                '%1$s / %2$s subscriber',
                '%1$s / %2$s subscribers',
                subscribersInPlan,
                'mailpoet',
              ),
              subscribersInPlan.toLocaleString(),
              subscribersInPlanLimit.toLocaleString(),
            )
          : sprintf(
              // translators: %s is number of subscribers on the site.
              _n(
                '%s subscriber',
                '%s subscribers',
                subscribersInPlan,
                'mailpoet',
              ),
              subscribersInPlan.toLocaleString(),
            )}
      </b>

      <Tooltip
        tooltip={__(
          'This is the total of subscribed, unconfirmed and inactive subscribers we count when you are sending with MailPoet Sending Service. The count excludes unsubscribed and bounced (invalid) email addresses.',
          'mailpoet',
        )}
        place="right"
      />
    </span>
  );
}
