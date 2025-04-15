import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { SubscribersInPlan } from 'common/subscribers-in-plan';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { SubscribersCacheMessage } from 'common/subscribers-cache-message';
import { PageHeader } from 'common/page-header';

function ListHeading(): JSX.Element {
  return (
    <>
      <TopBarWithBoundary />

      <PageHeader heading={__('Lists', 'mailpoet')}>
        <Link
          className="page-title-action"
          to="/new"
          data-automation-id="new-list"
        >
          {__('Add New List', 'mailpoet')}
        </Link>
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
    </>
  );
}

export { ListHeading };
