import { Link } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { plusIcon } from 'common/button/icon/plus';
import { SubscribersInPlan } from 'common/subscribers-in-plan';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { SubscribersCacheMessage } from 'common/subscribers-cache-message';

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

      <SubscribersInPlan
        subscribersInPlan={MailPoet.subscribersCount}
        subscribersInPlanLimit={MailPoet.subscribersLimit}
      />

      <SubscribersCacheMessage
        cacheCalculation={window.mailpoet_subscribers_counts_cache_created_at}
      />

      <MssAccessNotices />
    </>
  );
}

export { ListHeading };
