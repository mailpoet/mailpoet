import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const WelcomeWizardEmailCourseStep = props => (
  <div className="mailpoet_welcome_wizard_step_content">
    <h1>{MailPoet.I18n.t('welcomeWizardEmailCourseTitle')}</h1>
    <p>{MailPoet.I18n.t('welcomeWizardEmailCourseText')}</p>
    <iframe
      id="mailpoet_form_iframe"
      width="100%"
      scrolling="no"
      frameBorder="0"
      title="Apply to course"
      src="https://newsletters.mailpoet.com?mailpoet_form_iframe=13"
      className="mailpoet_form_iframe"
      marginWidth="0"
      marginHeight="0"
    />
    <button type="button" className="button button-primary" onClick={props.next}>{MailPoet.I18n.t('next')}</button>
  </div>
);

WelcomeWizardEmailCourseStep.propTypes = {
  next: PropTypes.func.isRequired,
};

export default WelcomeWizardEmailCourseStep;
