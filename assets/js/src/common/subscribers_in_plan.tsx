import React from 'react';
import MailPoet from 'mailpoet';
import HelpTooltip from 'help-tooltip.jsx';
import ReactStringReplace from 'react-string-replace';

type Props = {
  subscribersInPlan: number | false,
  subscribersInPlanLimit: number | false,
  mailpoetSubscribers: number | false,
  mailpoetSubscribersLimit: number | false,
  hasPremiumSupport: boolean,
  wpUsersCount: number | false,
  mssActive: boolean,
};

const SubscribersInPlan = ({
  subscribersInPlan,
  subscribersInPlanLimit,
  mailpoetSubscribers,
  mailpoetSubscribersLimit,
  hasPremiumSupport,
  wpUsersCount,
  mssActive,
}: Props) => {
  const subscribersInPlanCount = (
    <b key="1">
      {MailPoet.I18n.t('subscribersInPlanCount')
        .replace('%$1d', subscribersInPlan.toLocaleString())
        .replace('%$2d', subscribersInPlanLimit.toLocaleString())}
    </b>
  );
  const mailpoetSubscribersCount = (
    <b key="2">
      {MailPoet.I18n.t('subscribersInPlanCount')
        .replace('%$1d', mailpoetSubscribers.toLocaleString())
        .replace('%$2d', mailpoetSubscribersLimit ? mailpoetSubscribersLimit.toLocaleString() : '∞')}
    </b>
  );
  let mailpoetSubscribersTooltip;
  if (hasPremiumSupport) {
    mailpoetSubscribersTooltip = MailPoet.I18n.t('mailpoetSubscribersTooltipPremium');
  } else {
    mailpoetSubscribersTooltip = MailPoet.I18n.t('mailpoetSubscribersTooltipFree')
      .replace('%$1d', wpUsersCount.toLocaleString());
  }
  return (
    <div className="mailpoet-subscribers-in-plan">
      {mssActive && subscribersInPlanLimit && (
        <>
          {ReactStringReplace(MailPoet.I18n.t('subscribersInPlan'), '%s', () => subscribersInPlanCount)}
          {' '}
          <HelpTooltip
            tooltip={MailPoet.I18n.t('subscribersInPlanTooltip')}
            place="right"
          />
          <span className="mailpoet-subscribers-in-plan-spacer">{' '}</span>
        </>
      )}
      {ReactStringReplace(MailPoet.I18n.t('mailpoetSubscribers'), '%s', () => mailpoetSubscribersCount)}
      {' '}
      <HelpTooltip
        tooltip={mailpoetSubscribersTooltip}
        place="right"
      />
    </div>
  );
};

export default SubscribersInPlan;
