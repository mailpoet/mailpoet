import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

function renderHeader(newsletter) {
  if (newsletter.type === 'welcome') {
    return MailPoet.I18n.t('congratulationsWelcomeEmailSuccessHeader');
  }
  if (newsletter.type === 'notification') {
    return MailPoet.I18n.t('congratulationsPostNotificationSuccessHeader');
  }
  if (newsletter.type === 'automatic') {
    return MailPoet.I18n.t('congratulationsWooSuccessHeader');
  }
  if (newsletter.status === 'scheduled') {
    return MailPoet.I18n.t('congratulationsScheduleSuccessHeader');
  }
  return MailPoet.I18n.t('congratulationsSendSuccessHeader');
}

function MSSUserSuccess(props) {
  return (
    <div className="mailpoet_congratulate_success">
      <h1>{renderHeader(props.newsletter)}</h1>
      <img src={props.illustrationImageUrl} alt="" width="750" height="250" />
      <button type="button" className="button" onClick={props.successClicked}>{MailPoet.I18n.t('close')}</button>
    </div>
  );
}

MSSUserSuccess.propTypes = {
  successClicked: PropTypes.func.isRequired,
  illustrationImageUrl: PropTypes.string.isRequired,
  newsletter: PropTypes.shape({
    status: PropTypes.string.isRequired,
    type: PropTypes.string.isRequired,
  }).isRequired,
};

export default MSSUserSuccess;
