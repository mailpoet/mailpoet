import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const WelcomeWizardEmailCourseStep = props => (
  <div className="mailpoet_welcome_wizard_step_content mailpoet_welcome_wizard_centered_column">
    <h1>{MailPoet.I18n.t('welcomeWizardEmailCourseTitle')}</h1>
    <p>{MailPoet.I18n.t('welcomeWizardEmailCourseText')}</p>
    <div className="mailpoet_welcome_wizard_course_form_box">
      <img
        src={props.illustration_url}
        alt=""
        width="155"
        height="155"
      />
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
    </div>
    <button className="button button-primary" onClick={props.next}>{MailPoet.I18n.t('next')}</button>
  </div>
);

WelcomeWizardEmailCourseStep.propTypes = {
  next: PropTypes.func.isRequired,
  illustration_url: PropTypes.string.isRequired,
};

export default WelcomeWizardEmailCourseStep;
