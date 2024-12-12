import { __ } from '@wordpress/i18n';
import { QueueStatus } from 'newsletters/listings/queue-status';
import { Statistics } from 'newsletters/listings/statistics.jsx';
import { SegmentTags, FilterSegmentTag } from '../../common/tag/tags';
import { ErrorBoundary  } from '../../common';

export function getRow(tab, newsletter, segments, meta) {

    const selectSegementsFromIds = (segment_ids, segments) => {
        return segment_ids.map(segment_id => segments.find(segment => segment.id === segment_id));
      };

    return [
        { display: newsletter.subject, value: newsletter.subject },
        { display: <QueueStatus newsletter={newsletter} mailerLog={meta.mta_log} />, value: newsletter.status },
        { display: <div
            className="column mailpoet-hide-on-mobile"
            data-colname={__('Lists', 'mailpoet')}>
            <ErrorBoundary>
              <SegmentTags segments={selectSegementsFromIds(newsletter.segment_ids, segments)} dimension="large" />
              <FilterSegmentTag newsletter={newsletter} dimension="large" />
            </ErrorBoundary>
          </div>, value: newsletter.segment_ids },
        { display: <Statistics newsletter={newsletter} currentTime={meta.current_time} />, value: newsletter.statistics },
        { display: newsletter.sent_at ? newsletter.sent_at : 'Not sent', value: newsletter.sent_at || '' },
      ];
}
