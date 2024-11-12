import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Button } from '@wordpress/components';
import { plusIcon } from 'common/button/icon/plus';
import { PageHeader } from '../../common/page-header';

export function ListingHeading() {
  return (
    <PageHeader heading={__('Emails', 'mailpoet')}>
      <Button
        href="#/new"
        onClick={() => MailPoet.trackEvent('Emails clicked on New email')}
        icon={plusIcon}
        variant="primary"
        data-automation-id="new_email"
        className="mailpoet-button button-secondary"
      >
        {__('New email', 'mailpoet')}
      </Button>
    </PageHeader>
  );
}
