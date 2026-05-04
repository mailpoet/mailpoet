import { __ } from '@wordpress/i18n';
import { Link, useLocation, To } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { PageHeader } from 'common/page-header';
import { SubscribersInPlan } from 'common/subscribers-in-plan';
import { SubscribersCacheMessage } from 'common/subscribers-cache-message';
import { CompensateScreenOptions } from 'common/compensate-screen-options/compensate-screen-options';

export function SubscribersHeading() {
  const location = useLocation();

  return (
    <>
      <CompensateScreenOptions />
      <TopBarWithBoundary />
      <PageHeader
        className="mailpoet-subscribers-page-header"
        heading={__('Subscribers', 'mailpoet')}
      >
        <span className="mailpoet-subscribers-heading-actions">
          <span className="mailpoet-subscribers-heading-primary-actions">
            <Link
              className="page-title-action"
              to={
                {
                  pathname: '/new',
                  state: {
                    backUrl: location?.pathname,
                  },
                } as To
              }
            >
              <span data-automation-id="add-new-subscribers-button">
                {__('Add new subscriber', 'mailpoet')}
              </span>
            </Link>
            <a
              className="page-title-action"
              href="?page=mailpoet-import"
              data-automation-id="import-subscribers-button"
            >
              {__('Import', 'mailpoet')}
            </a>
            <a
              id="mailpoet_export_button"
              className="page-title-action"
              href="?page=mailpoet-export"
            >
              {__('Export', 'mailpoet')}
            </a>
          </span>
          <span className="mailpoet-subscribers-heading-management-actions">
            <a
              className="page-title-action"
              href="?page=mailpoet-tags"
              data-automation-id="manage-tags-button"
            >
              {__('Tags', 'mailpoet')}
            </a>
            <a
              className="page-title-action"
              href="?page=mailpoet-custom-fields"
              data-automation-id="manage-custom-fields-button"
            >
              {__('Custom fields', 'mailpoet')}
            </a>
          </span>
        </span>
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
