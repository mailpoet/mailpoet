import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const Step = (props) => (
  <div className="mailpoet_welcome_wizard_step_content">

  </div>
);

Step.propTypes = {
  next: PropTypes.func.isRequired,
  subscribersCount: PropTypes.number.isRequired,
};

export default Step;
