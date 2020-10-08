import React from 'react';
import PropTypes from 'prop-types';
import { Link, withRouter } from 'react-router-dom';
import MailPoet from 'mailpoet';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import plusIcon from 'common/button/icon/plus';

const ListingHeading = ({ history }) => (
  <>
    <TopBarWithBeamer onLogoClick={() => history.push('/')}>
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

ListingHeading.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default withRouter(ListingHeading);
