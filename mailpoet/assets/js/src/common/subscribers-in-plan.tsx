import ReactStringReplace from 'react-string-replace';
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { Tooltip } from 'help-tooltip.jsx';
import { Icon, Tooltip as WPTooltip } from '@wordpress/components';
import { help } from '@wordpress/icons';

type Props = {
  subscribersInPlan: number | false;
  subscribersInPlanLimit: number | false;
  design?: 'old' | 'new'; // temporary property while some pages are using the old design (lists and subscribers) and some are using the new design (segments)
};

export function SubscribersInPlan({
  subscribersInPlan,
  subscribersInPlanLimit,
  design = 'old',
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

  return design === 'old' ? (
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
  ) : (
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

      <WPTooltip
        text={__(
          'This is the total of subscribed, unconfirmed and inactive subscribers we count when you are sending with MailPoet Sending Service. The count excludes unsubscribed and bounced (invalid) email addresses.',
          'mailpoet',
        )}
      >
        <div className="mailpoet-segment-help-icon">
          <Icon icon={help} />
        </div>
      </WPTooltip>
    </span>
  );
}
