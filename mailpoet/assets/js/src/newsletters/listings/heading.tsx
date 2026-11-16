import { Link } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { PageHeader } from 'common/page-header';
import { CompensateScreenOptions } from 'common/compensate-screen-options/compensate-screen-options';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';

const isEmailPresetsEnabled = MailPoet.FeaturesController.isSupported(
  MailPoet.FeaturesController.FEATURE_EMAIL_PRESETS,
);

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
          {__('Add new email', 'mailpoet')}
        </Link>
        {isEmailPresetsEnabled && (
          <a href={MailPoet.urls.emailTemplates} className="page-title-action">
            {__('Manage templates', 'mailpoet')}
          </a>
        )}
      </PageHeader>
    </>
  );
}
