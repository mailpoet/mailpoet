import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { plusIcon } from 'common/button/icon/plus';
import { SubscribersInPlan } from 'common/subscribers-in-plan';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { SubscribersCacheMessage } from 'common/subscribers-cache-message';
import { PageHeader } from 'common/page-header';

function ListHeading(): JSX.Element {
  return (
    <>
      <TopBarWithBoundary>
        <Link
          className="mailpoet-button button-secondary"
          to="/new"
          data-automation-id="new-list"
        >
          {plusIcon}
          <span>{MailPoet.I18n.t('new')}</span>
        </Link>
      </TopBarWithBoundary>

      <PageHeader heading={__('Lists', 'mailpoet')} />

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
    </>
  );
}

export { ListHeading };
