import React from 'react';
import { Link } from 'react-router-dom';
import MailPoet from 'mailpoet';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import plusIcon from 'common/button/icon/plus';

const ListingHeading = () => (
  <>
    <TopBarWithBeamer>
      <Link
        id="mailpoet-new-email"
        className="mailpoet-button"
        to="/new"
        onClick={() => MailPoet.trackEvent(
          'Emails > Add New',
          { 'MailPoet Free version': window.mailpoet_version }
        )}
        data-automation-id="new_email"
      >
        {plusIcon}
        <span>{MailPoet.I18n.t('new')}</span>
      </Link>
    </TopBarWithBeamer>
    <h1 className="mailpoet-newsletter-listing-heading-empty title">{' '}</h1>
  </>
);

export default ListingHeading;
