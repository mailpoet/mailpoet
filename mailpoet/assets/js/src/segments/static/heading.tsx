import { Link } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { TopBarWithBeamer } from 'common/top-bar/top-bar';
import { plusIcon } from 'common/button/icon/plus';
import { SubscribersInPlan } from 'common/subscribers-in-plan';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { SubscribersCacheMessage } from 'common/subscribers-cache-message';

function ListHeading(): JSX.Element {
  return (
    <>
      <TopBarWithBeamer>
        <Link
          className="mailpoet-button button-secondary"
          to="/new"
          data-automation-id="new-list"
        >
          {plusIcon}
          <span>{MailPoet.I18n.t('new')}</span>
        </Link>
      </TopBarWithBeamer>

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
