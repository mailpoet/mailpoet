import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';

type Props = {
  cacheCalculation: string;
};

export function SubscribersCacheMessage({ cacheCalculation }: Props): JSX.Element {
  const [loading, setLoading] = useState(false);
  const datetimeDiff = new Date().getTime() - new Date(cacheCalculation).getTime();
  const minutes = Math.floor((datetimeDiff / 1000) / 60);

  const handleRecalculate = () => {
    setLoading(true);
    MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'settings',
      action: 'recalculateSubscribersCountsCache',
    }).done(() => {
      window.location.reload();
    }).fail((response) => {
      MailPoet.Notice.error(
        response.errors.map((error) => error.message),
        { scroll: true }
      );
    });
  };

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
        onClick={handleRecalculate}
        withSpinner={loading}
      >
        {MailPoet.I18n.t('recalculateNow')}
      </Button>
      <div className="mailpoet-gap" />
    </div>
  );
}
