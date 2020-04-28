import React from 'react';
import MailPoet from 'mailpoet';
import HelpTooltip from 'help-tooltip.jsx';

type Props = {
  subscribersInPlan: number | false,
  hasValidApiKey: boolean,
};

const SubscribersInPlan = ({ subscribersInPlan, hasValidApiKey }: Props) => {
  if (!subscribersInPlan) return null;
  if (!hasValidApiKey) return null;
  return (
    <h3>
      {MailPoet.I18n.t('subscribersInPlan')
        .replace('%$1d', subscribersInPlan.toLocaleString())}
      {' '}
      <HelpTooltip
        tooltip={MailPoet.I18n.t('subscribersInPlanTooltip')}
        place="right"
      />
    </h3>
  );
};

export default SubscribersInPlan;
