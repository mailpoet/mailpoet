import React from 'react';
import { Link } from 'react-router-dom';
import MailPoet from 'mailpoet';

const ListingHeading = () => (
  <div>
    <h1 className="mailpoet-newsletter-listing-heading title">
      {MailPoet.I18n.t('pageTitle')}
      <Link
        id="mailpoet-new-email"
        className="page-title-action"
        to="/new"
        onClick={() => MailPoet.trackEvent(
          'Emails > Add New',
          { 'MailPoet Free version': window.mailpoet_version }
        )}
        data-automation-id="new_email"
      >
        {MailPoet.I18n.t('new')}
      </Link>
    </h1>
  </div>
);

export default ListingHeading;
