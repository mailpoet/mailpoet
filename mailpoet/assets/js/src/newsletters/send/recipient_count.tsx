import { __ } from '@wordpress/i18n';
import { LoadingSpinner } from 'common/loader/loading_spinner';
import { Tooltip } from 'common/tooltip/tooltip';
import { useState, useEffect, useMemo } from 'react';
import { NewsLetter } from 'common/newsletter';
import { MailPoet } from 'mailpoet';

type RecipientCountProps = {
  item: NewsLetter;
};

export function RecipientCount(props: RecipientCountProps) {
  const [isLoading, setIsLoading] = useState(true);
  const [recipientCount, setRecipientCount] = useState(0);

  const segmentIds = useMemo(
    () => (props.item.segments || []).map((segment) => segment.id),
    [props.item.segments],
  );
  const filterSegmentId = useMemo(
    () => props.item.options?.filterSegmentId || null,
    [props.item.options?.filterSegmentId],
  );

  useEffect(() => {
    if (segmentIds.length < 1) {
      setRecipientCount(0);
      setIsLoading(false);
      return;
    }

    setIsLoading(true);

    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'segments',
      action: 'subscriberCount',
      data: {
        segmentIds,
        filterSegmentId,
      },
    })
      .done((response) => {
        setRecipientCount(response.data.count as number);
      })
      .always(() => setIsLoading(false));
  }, [segmentIds, filterSegmentId]);

  return (
    <div>
      {__('Estimated recipients', 'mailpoet')}:
      {isLoading ? (
        <LoadingSpinner
          className="mailpoet-recipient-count-spinner"
          alt={__('Loading recipient count', 'mailpoet')}
        />
      ) : (
        <>
          <Tooltip place="right" multiline id="estimated-count-tooltip">
            {__('This count may change at the time of sending.', 'mailpoet')}
          </Tooltip>
          <span
            data-tip
            data-for="estimated-count-tooltip"
            className="estimated-recipient-count"
          >
            {recipientCount.toLocaleString()}
          </span>
        </>
      )}
    </div>
  );
}
