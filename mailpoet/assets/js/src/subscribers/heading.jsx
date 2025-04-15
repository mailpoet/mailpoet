import { __ } from '@wordpress/i18n';
import { Link, useLocation } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { plusIcon } from 'common/button/icon/plus';
import { PageHeader } from 'common/page-header';
import { SubscribersInPlan } from 'common/subscribers-in-plan';
import { SubscribersCacheMessage } from 'common/subscribers-cache-message';

export function SubscribersHeading() {
  const location = useLocation();

  return (
    <>
      <TopBarWithBoundary>
        <Link
          className="mailpoet-button button-secondary"
          to={{
            pathname: '/new',
            state: {
              backUrl: location?.pathname,
            },
          }}
        >
          {plusIcon}
          <span data-automation-id="add-new-subscribers-button">
            {MailPoet.I18n.t('new')}
          </span>
        </Link>
        <a
          className="mailpoet-button not-small-screen button-secondary"
          href="?page=mailpoet-import"
          data-automation-id="import-subscribers-button"
        >
          {MailPoet.I18n.t('import')}
        </a>
        <a
          id="mailpoet_export_button"
          className="mailpoet-button not-small-screen button-secondary"
          href="?page=mailpoet-export"
        >
          {MailPoet.I18n.t('export')}
        </a>
      </TopBarWithBoundary>
      <PageHeader heading={__('Subscribers', 'mailpoet')} />

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
    </>
  );
}
