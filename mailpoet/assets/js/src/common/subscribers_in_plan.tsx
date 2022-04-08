import MailPoet from 'mailpoet';
import HelpTooltip from 'help-tooltip.jsx';
import ReactStringReplace from 'react-string-replace';

type Props = {
  subscribersInPlan: number | false;
  subscribersInPlanLimit: number | false;
};

function SubscribersInPlan({
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
      <HelpTooltip
        tooltip={MailPoet.I18n.t('subscribersInPlanTooltip')}
        place="right"
      />
      <span className="mailpoet-subscribers-in-plan-spacer"> </span>
    </div>
  );
}

export default SubscribersInPlan;
