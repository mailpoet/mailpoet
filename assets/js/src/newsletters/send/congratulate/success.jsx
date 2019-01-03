import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

function renderHeader(newsletter) {
  if (newsletter.type === 'welcome') {
    return MailPoet.I18n.t('congratulationsWelcomeEmailSuccessHeader');
  } else if (newsletter.type === 'notification') {
    return MailPoet.I18n.t('congratulationsPostNotificationSuccessHeader');
  } else if (newsletter.type === 'automatic') {
    return MailPoet.I18n.t('congratulationsWooSuccessHeader');
  } else if (newsletter.status === 'scheduled') {
    return MailPoet.I18n.t('congratulationsScheduleSuccessHeader');
  }
  return MailPoet.I18n.t('congratulationsSendSuccessHeader');
}

function Success(props) {
  const showSuccessDeliveryPoll = (
    props.newsletter.type === 'standard' &&
    props.newsletter.status !== 'scheduled'
  );
  if (showSuccessDeliveryPoll) {
    MailPoet.Poll.successDelivery.initTypeformScript();
  }
  return (
    <div className="mailpoet_congratulate_success">
      <h1>{renderHeader(props.newsletter)}</h1>
      <img src={props.illustrationImageUrl} alt="" width="750" height="250" />
      {showSuccessDeliveryPoll &&
        <div
          className="typeform-widget"
          data-url="https://mailpoet.typeform.com/to/ciWID6"
          data-transparency="100"
          data-hide-headers="true"
          data-hide-footer="true"
        />
      }
      <button className="button" onClick={props.successClicked}>{MailPoet.I18n.t('close')}</button>
    </div>
  );
}

Success.propTypes = {
  successClicked: PropTypes.func.isRequired,
  illustrationImageUrl: PropTypes.string.isRequired,
  newsletter: PropTypes.shape({
    status: PropTypes.string.isRequired,
    type: PropTypes.string.isRequired,
  }).isRequired,
};


export default Success;
