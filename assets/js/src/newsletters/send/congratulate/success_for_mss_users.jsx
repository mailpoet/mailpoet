import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import Heading from 'common/typography/heading/heading';
import { Button } from 'common';

function getSuccessMessage(newsletter) {
  if (newsletter.type === 'welcome') {
    return MailPoet.I18n.t('congratulationsWelcomeEmailSuccessBody');
  }
  if (newsletter.type === 'notification') {
    return MailPoet.I18n.t('congratulationsPostNotificationSuccessBody');
  }
  if (newsletter.type === 'automatic') {
    return MailPoet.I18n.t('congratulationsWooSuccessBody');
  }
  if (newsletter.status === 'scheduled') {
    return MailPoet.I18n.t('congratulationsScheduleSuccessBody');
  }
  return MailPoet.I18n.t('congratulationsSendSuccessBody');
}

function MSSUserSuccess(props) {
  return (
    <div className="mailpoet_congratulate_success">
      <Heading level={0}>{MailPoet.I18n.t('congratulationsSuccessHeader')}</Heading>
      <h1>{getSuccessMessage(props.newsletter)}</h1>
      <img src={props.illustrationImageUrl} alt="" width="500" />
      <Button type="button" dimension="small" onClick={props.successClicked}>{MailPoet.I18n.t('close')}</Button>
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
