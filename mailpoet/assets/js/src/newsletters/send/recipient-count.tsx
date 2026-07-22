import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { Tooltip } from 'common/tooltip/tooltip';
import { useState, useEffect, useMemo, useRef } from 'react';
import { NewsLetter } from 'common/newsletter';
import { MailPoet } from 'mailpoet';

type RecipientCountProps = {
  item: NewsLetter;
};

function configString(segmentIds: string[], filterSegmentId?: string) {
  return `segments:${segmentIds.join(',')}|filterSegmentId:${filterSegmentId}`;
}

export function RecipientCount(props: RecipientCountProps) {
  const [isLoading, setIsLoading] = useState(true);
  const [recipientCount, setRecipientCount] = useState(0);
  const [hasError, setHasError] = useState(false);

  const segmentIds = useMemo(
    () => (props.item.segments || []).map((segment) => segment.id),
    [props.item.segments],
  );
  const filterSegmentId = useMemo(
    () => props.item.options?.filterSegmentId || null,
    [props.item.options?.filterSegmentId],
  );

  const configBeforeRef = useRef('');

  const apiResponseCache = useRef({});

  useEffect(() => {
    const currentConfigString = configString(segmentIds, filterSegmentId);
    configBeforeRef.current = currentConfigString;

    if (segmentIds.length < 1) {
      setRecipientCount(0);
      setHasError(false);
      setIsLoading(false);
      return;
    }

    if (currentConfigString in apiResponseCache.current) {
      setRecipientCount(
        apiResponseCache.current[currentConfigString] as number,
      );
      setHasError(false);
      setIsLoading(false);
      return;
    }

    setIsLoading(true);
    setHasError(false);
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
        const calculatedCount = response.data.count;
        apiResponseCache.current[currentConfigString] =
          calculatedCount as number;
        const configAfter = configString(segmentIds, filterSegmentId);
        if (configBeforeRef.current === configAfter) {
          setRecipientCount(calculatedCount as number);
        }
      })
      .fail(() => {
        // Show an "Unavailable" label instead of dropping to zero, and don't
        // cache the failure so a later selection change retries.
        const configAfter = configString(segmentIds, filterSegmentId);
        if (configBeforeRef.current === configAfter) {
          setHasError(true);
        }
      })
      .always(() => setIsLoading(false));
  }, [segmentIds, filterSegmentId]);

  return (
    <div>
      {__('Estimated recipients', 'mailpoet')}:
      {isLoading && (
        <Spinner
          // eslint-disable-next-line @typescript-eslint/ban-ts-comment
          // @ts-ignore -- typescript thinks Spinner doesn't accept className but it does
          className="mailpoet-recipient-count-spinner"
        />
      )}
      {!isLoading && !hasError && (
        <>
          <Tooltip place="right" id="estimated-count-tooltip">
            {__('This count may change at the time of sending.', 'mailpoet')}
          </Tooltip>
          <span
            data-tip
            data-tooltip-id="estimated-count-tooltip"
            className="estimated-recipient-count"
          >
            {recipientCount.toLocaleString()}
          </span>
        </>
      )}
      {!isLoading && hasError && (
        <>
          <Tooltip place="right" id="estimated-count-tooltip">
            {__(
              "We couldn't calculate the number of recipients. You can still send this email.",
              'mailpoet',
            )}
          </Tooltip>
          <span
            data-tip
            data-tooltip-id="estimated-count-tooltip"
            className="estimated-recipient-count"
          >
            {__('Unavailable', 'mailpoet')}
          </span>
        </>
      )}
    </div>
  );
}
