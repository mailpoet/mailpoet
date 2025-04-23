import { Link } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { PageHeader } from 'common/page-header';
import { CompensateScreenOptions } from 'common/compensate-screen-options/compensate-screen-options';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';

export function ListingHeading(): JSX.Element {
  return (
    <>
      <CompensateScreenOptions />
      <TopBarWithBoundary />
      <PageHeader heading={__('Emails', 'mailpoet')}>
        <Link
          id="mailpoet-new-email"
          className="page-title-action"
          to="/new"
          onClick={() => {
            MailPoet.trackEvent('Emails > Add New');
          }}
          data-automation-id="new_email"
        >
          {__('Add New Email', 'mailpoet')}
        </Link>
      </PageHeader>
    </>
  );
}
