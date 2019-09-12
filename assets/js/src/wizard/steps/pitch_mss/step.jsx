import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const FreePlanSubscribers = () => (
  <>
    <h1>{MailPoet.I18n.t('welcomeWizardMSSFreeTitle')}</h1>
  </>
);

const NotFreePlanSubscribers = () => (
  <>
    <h1>{MailPoet.I18n.t('welcomeWizardMSSNotFreeTitle')}</h1>
  </>
);

const Step = (props) => (
  <div className="mailpoet_welcome_wizard_step_content">
    { props.subscribersCount < 1000
      ? (
        <FreePlanSubscribers />
      ) : (
        <NotFreePlanSubscribers />
      )
    }
  </div>
);

Step.propTypes = {
  next: PropTypes.func.isRequired,
  subscribersCount: PropTypes.number.isRequired,
};

export default Step;
