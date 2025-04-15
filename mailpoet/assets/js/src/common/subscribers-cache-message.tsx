import { useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';
import { Notice } from '../notices/notice';

type Props = {
  cacheCalculation: string;
};

export function SubscribersCacheMessage({
  cacheCalculation,
}: Props): JSX.Element {
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState([]);
  const datetimeDiff =
    new Date().getTime() - new Date(cacheCalculation).getTime();
  const minutes = Math.floor(datetimeDiff / 1000 / 60);

  const handleRecalculate = () => {
    setLoading(true);
    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'settings',
      action: 'recalculateSubscribersCountsCache',
    })
      .done(() => {
        window.location.reload();
      })
      .fail((response: ErrorResponse) => {
        setLoading(false);
        if (!(response && response.errors && response.errors.length)) return;

        if (JSON.stringify(response.errors).includes('reinstall_plugin')) {
          MailPoet.Notice.showApiErrorNotice(response, {
            static: true,
            scroll: true,
          });
        } else {
          setErrors(response.errors.map((error) => error.message));
        }
      });
  };

  return (
    <>
      <span className="mailpoet-segment-subscriber-cache">
        {sprintf(
          // translators: %s is how many minutes passed since the subscribed cache was calculated.
          _n(
            'Calculated %s min ago',
            'Calculated %s mins ago',
            minutes,
            'mailpoet',
          ),
          minutes.toLocaleString(),
        )}
      </span>
      <Button
        variant="tertiary"
        onClick={handleRecalculate}
        withSpinner={loading}
      >
        {__('Recalculate', 'mailpoet')}
      </Button>
      {errors.length > 0 && (
        <Notice type="error">
          {errors.map((error) => (
            <p key={error}>{error}</p>
          ))}
        </Notice>
      )}
    </>
  );
}
