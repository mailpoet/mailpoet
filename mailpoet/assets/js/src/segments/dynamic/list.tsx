import { HashRouter } from 'react-router-dom';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ListingTabs } from './list/listing-tabs';
import { Notices } from './list/notices';
import * as ROUTES from '../routes';
import { plusIcon } from '../../common/button/icon/plus';
import { PageHeader } from '../../common/page-header';
import { SubscribersCacheMessage } from '../../common/subscribers-cache-message';
import { SubscribersInPlan } from '../../common/subscribers-in-plan';
import { TopBarWithBeamer } from '../../common/top-bar/top-bar';
import { MailPoet } from '../../mailpoet';
import { MssAccessNotices } from '../../notices/mss-access-notices';

export function DynamicSegmentList(): JSX.Element {
  return (
    <HashRouter>
      <TopBarWithBeamer hideScreenOptions />
      <Notices />

      <PageHeader heading={__('Segments', 'mailpoet')}>
        <Button
          href={`#${ROUTES.DYNAMIC_SEGMENT_TEMPLATES}`}
          icon={plusIcon}
          variant="primary"
          data-automation-id="new-segment"
          className="mailpoet-add-new-button"
        >
          {__('New segment', 'mailpoet')}
        </Button>
      </PageHeader>

      <div className="mailpoet-segment-subscriber-count">
        <SubscribersInPlan
          subscribersInPlan={MailPoet.subscribersCount}
          subscribersInPlanLimit={MailPoet.subscribersLimit}
          design="new"
        />
        <SubscribersCacheMessage
          cacheCalculation={window.mailpoet_subscribers_counts_cache_created_at}
          design="new"
        />
      </div>
      <MssAccessNotices />
      <ListingTabs />
    </HashRouter>
  );
}
