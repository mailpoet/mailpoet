import { Link } from 'react-router-dom';
import MailPoet from 'mailpoet';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import plusIcon from 'common/button/icon/plus';
import SubscribersInPlan from 'common/subscribers_in_plan';
import SubscribersLimitNotice from 'notices/subscribers_limit_notice';
import EmailVolumeLimitNotice from 'notices/email_volume_limit_notice';
import InvalidMssKeyNotice from 'notices/invalid_mss_key_notice';
import { SubscribersCacheMessage } from 'common/subscribers_cache_message';

function ListHeading() {
  return (
    <>
      <TopBarWithBeamer>
        <Link className="mailpoet-button button-secondary" to="/new">
          {plusIcon}
          <span>{MailPoet.I18n.t('new')}</span>
        </Link>
        <Link
          className="mailpoet-button button-secondary"
          to="/new-segment"
          data-automation-id="new-segment"
        >
          {plusIcon}
          <span>{MailPoet.I18n.t('newSegment')}</span>
        </Link>
      </TopBarWithBeamer>

      <SubscribersInPlan
        subscribersInPlan={MailPoet.subscribersCount}
        subscribersInPlanLimit={MailPoet.subscribersLimit}
      />

      <SubscribersCacheMessage
        cacheCalculation={window.mailpoet_subscribers_counts_cache_created_at}
      />

      <SubscribersLimitNotice />
      <EmailVolumeLimitNotice />
      <InvalidMssKeyNotice
        mssKeyInvalid={MailPoet.hasInvalidMssApiKey}
        subscribersCount={MailPoet.subscribersCount}
      />
    </>
  );
}

export default ListHeading;
