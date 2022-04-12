import ReactStringReplace from 'react-string-replace';
import { MailPoet } from 'mailpoet';
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
      {MailPoet.I18n.t('subscribersInPlanCount')
        .replace('%1$d', subscribersInPlan.toLocaleString())
        .replace('%2$d', subscribersInPlanLimit.toLocaleString())}
    </b>
  ) : (
    <b key="subscribers_count">{subscribersInPlan}</b>
  );

  return (
    <div className="mailpoet-subscribers-in-plan">
      {ReactStringReplace(
        MailPoet.I18n.t('subscribersInPlan'),
        '%s',
        () => subscribersInPlanCount,
      )}{' '}
      <Tooltip
        tooltip={MailPoet.I18n.t('subscribersInPlanTooltip')}
        place="right"
      />
      <span className="mailpoet-subscribers-in-plan-spacer"> </span>
    </div>
  );
}
