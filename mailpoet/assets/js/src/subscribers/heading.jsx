import { __ } from '@wordpress/i18n';
import { Link, useLocation } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { PageHeader } from 'common/page-header';
import { SubscribersInPlan } from 'common/subscribers-in-plan';
import { SubscribersCacheMessage } from 'common/subscribers-cache-message';
import { CompensateScreenOptions } from 'common/compensate-screen-options/compensate-screen-options';

export function SubscribersHeading() {
  const location = useLocation();

  return (
    <>
      <CompensateScreenOptions />
      <PageHeader heading={__('Subscribers', 'mailpoet')}>
        <Link
          className="page-title-action"
          to={{
            pathname: '/new',
            state: {
              backUrl: location?.pathname,
            },
          }}
        >
          <span data-automation-id="add-new-subscribers-button">
            {__('Add New Subscriber', 'mailpoet')}
          </span>
        </Link>
        <a
          className="page-title-action not-small-screen"
          href="?page=mailpoet-import"
          data-automation-id="import-subscribers-button"
        >
          {__('Import', 'mailpoet')}
        </a>
        <a
          id="mailpoet_export_button"
          className="page-title-action not-small-screen"
          href="?page=mailpoet-export"
        >
          {__('Export', 'mailpoet')}
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
    </>
  );
}
