import { __ } from '@wordpress/i18n';
import { ListingTabs } from './list/listing-tabs';
import { Notices } from './list/notices';
import * as ROUTES from '../routes';
import { PageHeader } from '../../common/page-header';
import { SubscribersCacheMessage } from '../../common/subscribers-cache-message';
import { SubscribersInPlan } from '../../common/subscribers-in-plan';
import { TopBarWithBoundary } from '../../common/top-bar/top-bar';
import { MailPoet } from '../../mailpoet';
import { MssAccessNotices } from '../../notices/mss-access-notices';

export function DynamicSegmentList(): JSX.Element {
  return (
    <>
      <TopBarWithBoundary hideScreenOptions />
      <Notices />

      <PageHeader heading={__('Segments', 'mailpoet')}>
        <a
          href={`#${ROUTES.DYNAMIC_SEGMENT_TEMPLATES}`}
          data-automation-id="new-segment"
          className="page-title-action"
        >
          {__('Add new segment', 'mailpoet')}
        </a>
      </PageHeader>

      <div className="mailpoet-segment-subscriber-count">
        <SubscribersInPlan
          subscribersInPlan={MailPoet.subscribersCount}
          subscribersInPlanLimit={MailPoet.subscribersLimit}
        />
        <SubscribersCacheMessage
          cacheCalculation={window.mailpoet_subscribers_counts_cache_created_at}
        />
      </div>
      <MssAccessNotices />
      <ListingTabs />
    </>
  );
}
