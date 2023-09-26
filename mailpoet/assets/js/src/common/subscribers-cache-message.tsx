import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';
import ReactStringReplace from 'react-string-replace';
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
        setErrors(response.errors.map((error) => error.message));
        setLoading(false);
      });
  };

  return (
    <div className="mailpoet-subscribers-cache-notice">
      {ReactStringReplace(
        __(
          'Lists and Segments subscribers counts were calculated <abbr>{$mins} minutes ago</abbr>',
          'mailpoet',
        ),
        /<abbr>(.*?)<\/abbr>/,
        (match, i) => (
          <abbr key={i} title={cacheCalculation}>
            {match.replace(/(\{\$mins\}|\$mins)/i, String(minutes))}
          </abbr>
        ),
      )}

      <Button
        className="mailpoet-subscribers-cache-notice-button"
        type="button"
        variant="secondary"
        dimension="small"
        onClick={handleRecalculate}
        withSpinner={loading}
      >
        {__('Recalculate now', 'mailpoet')}
      </Button>
      <div className="mailpoet-gap" />
      {errors.length > 0 && (
        <Notice type="error">
          {errors.map((error) => (
            <p key={error}>{error}</p>
          ))}
        </Notice>
      )}
    </div>
  );
}
