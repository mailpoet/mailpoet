import React from 'react';
import PropTypes from 'prop-types';
import { Link, withRouter } from 'react-router-dom';
import MailPoet from 'mailpoet';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import plusIcon from 'common/button/icon/plus';

const FormsHeading = ({ history }) => {
  const goToSelectTemplate = () => {
    setTimeout(() => {
      window.location = window.mailpoet_form_template_selection_url;
    }, 200); // leave some time for the event to track
  };

  return (
    <>
      <TopBarWithBeamer onLogoClick={() => history.push('/')}>
        <Link
          id="mailpoet-new-email"
          className="mailpoet-button"
          to="/new"
          onClick={goToSelectTemplate}
          data-automation-id="create_new_form"
        >
          {plusIcon}
          <span>{MailPoet.I18n.t('new')}</span>
        </Link>
      </TopBarWithBeamer>
      <h1 className="mailpoet-newsletter-listing-heading-empty title">{' '}</h1>
    </>
  );
};

FormsHeading.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default withRouter(FormsHeading);
