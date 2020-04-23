import React from 'react';
import MailPoet from 'mailpoet';
import HelpTooltip from 'help-tooltip.jsx';

type Props = {
  subscribersLimit: number | false,
};

const SubscribersLimit = ({ subscribersLimit }: Props) => {
  if (!subscribersLimit) return null;
  return (
    <h3>
      {MailPoet.I18n.t('subscribersInPlan')
        .replace('%$1d', subscribersLimit.toLocaleString())}
      {' '}
      <HelpTooltip
        tooltip={MailPoet.I18n.t('subscribersInPlanTooltip')}
        place="right"
      />
    </h3>
  );
};

export default SubscribersLimit;
