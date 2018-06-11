import React from 'react';
import { Link } from 'react-router';
import MailPoet from 'mailpoet';

const ListingHeading = () => (
  <h1 className="title">
    {MailPoet.I18n.t('pageTitle')}
    <Link
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
);


module.exports = ListingHeading;
