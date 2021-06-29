import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';

type Props = {
  cacheCalculation: string;
};

const handleRecalculate = () => {
  window.location.reload();
};

const SubscribersCacheMessage = ({ cacheCalculation }: Props): JSX.Element => {
  const [loading, setLoading] = useState(false);
  const datetimeDiff = new Date().getTime() - new Date(cacheCalculation).getTime();
  const minutes = Math.floor((datetimeDiff / 1000) / 60);
  return (
    <div className="mailpoet-subscribers-cache-notice">
      {MailPoet.I18n.t('subscribersCountWereCalculated')}
      &nbsp;
      <abbr title={cacheCalculation}>{`${String(minutes)} ${String(MailPoet.I18n.t('subscribersMinutesAgo'))}`}</abbr>
      <Button
        className="mailpoet-subscribers-cache-notice-button"
        type="button"
        variant="secondary"
        dimension="small"
        onClick={() => {
          setLoading(true);
          handleRecalculate();
        }}
        withSpinner={loading}
      >
        {MailPoet.I18n.t('recalculateNow')}
      </Button>
      <div className="mailpoet-gap" />
    </div>
  );
};

export { SubscribersCacheMessage };
