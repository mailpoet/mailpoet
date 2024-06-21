import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { Tooltip } from 'common/tooltip/tooltip';
import { useState, useEffect, useMemo, useRef } from 'react';
import { NewsLetter } from 'common/newsletter';
import { MailPoet } from 'mailpoet';

type RecipientCountProps = {
  item: NewsLetter;
};

function configString(segmentIds: string[], filterSegpmentId?: string) {
  return `segments:${segmentIds.join(',')}|filterSegmentId:${filterSegpmentId}`;
}

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

  const configBeforeRef = useRef('');

  const apiResponseCache = useRef({});

  useEffect(() => {
    const currentConfigString = configString(segmentIds, filterSegmentId);
    configBeforeRef.current = currentConfigString;

    if (segmentIds.length < 1) {
      setRecipientCount(0);
      setIsLoading(false);
      return;
    }

    if (currentConfigString in apiResponseCache.current) {
      setRecipientCount(
        apiResponseCache.current[currentConfigString] as number,
      );
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
        const calculatedCount = response.data.count;
        apiResponseCache.current[currentConfigString] =
          calculatedCount as number;
        const configAfter = configString(segmentIds, filterSegmentId);
        if (configBeforeRef.current === configAfter) {
          setRecipientCount(calculatedCount as number);
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
      {!isLoading && (
        <>
          <Tooltip place="right" id="estimated-count-tooltip">
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
