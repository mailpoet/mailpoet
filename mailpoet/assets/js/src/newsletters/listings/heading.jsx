import { Link } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { TopBarWithBeamer } from 'common/top-bar/top-bar';
import { plusIcon } from 'common/button/icon/plus';

export function ListingHeading() {
  return (
    <TopBarWithBeamer>
      <Link
        id="mailpoet-new-email"
        className="mailpoet-button button-secondary"
        to="/new"
        onClick={() => MailPoet.trackEvent('Emails > Add New')}
        data-automation-id="new_email"
      >
        {plusIcon}
        <span>{__('New email', 'mailpoet')}</span>
      </Link>
    </TopBarWithBeamer>
  );
}
