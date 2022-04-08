import { useState } from 'react';
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
  const [isClosing, setIsClosing] = useState(false);
  return (
    <>
      <Heading level={0}>
        {MailPoet.I18n.t('congratulationsSuccessHeader')}
      </Heading>
      <Heading level={3}>{getSuccessMessage(props.newsletter)}</Heading>
      <div className="mailpoet-gap-large" />
      <div className="mailpoet-gap-large" />
      <img src={props.illustrationImageUrl} alt="" width="500" />
      <div className="mailpoet-gap-large" />
      <Button
        type="button"
        dimension="small"
        onClick={() => {
          props.successClicked();
          setIsClosing(true);
        }}
        withSpinner={isClosing}
      >
        {MailPoet.I18n.t('close')}
      </Button>
    </>
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
