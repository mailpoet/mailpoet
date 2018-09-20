import React from 'react';
import { Link } from 'react-router';
import MailPoet from 'mailpoet';
import InAppAnnoucement from 'in_app_announcements/in_app_announcement.jsx';

const ListingHeading = () => (
  <h1 className="title">
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
    <InAppAnnoucement
      className="mailpoet_in_app_announcement_free_welcome_emails_dot"
      showToNewUser={false}
      showToPremiumUser={false}
      showOnlyOnceSlug="free_welcome_emails"
      height="650px"
      validUntil={new Date('2018-10-31').getTime() / 1000}
    >
      <div className="mailpoet_in_app_announcement_free_welcome_emails">
        <h2>{MailPoet.I18n.t('freeWelcomeEmailsHeading')}</h2>
        <img
          src={window.mailpoet_free_welcome_emails_image}
          alt={MailPoet.I18n.t('freeWelcomeEmailsHeading')}
        />
        <p>{MailPoet.I18n.t('freeWelcomeEmailsParagraph')}</p>
      </div>
    </InAppAnnoucement>
  </h1>
);


module.exports = ListingHeading;
