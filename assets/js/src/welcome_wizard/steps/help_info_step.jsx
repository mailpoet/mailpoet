import React from 'react';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

const WelcomeWizardHelpInfoStep = props => (
  <div className="mailpoet_welcome_wizard_step_content mailpoet_welcome_wizard_centered_column">
    <div className="mailpoet_welcome_wizard_help_info_block">
      <span className="mailpoet_welcome_wizard_support_button" />
      <p>
        {
          ReactStringReplace(
            MailPoet.I18n.t('welcomeWizardYouCanContactText'),
            /\[strong\](.*?)\[\/strong\]/g,
            match => (<strong key={match}>{ match }</strong>)
          )
        }
      </p>
    </div>
    <div className="mailpoet_welcome_wizard_help_info_block">
      <span className="mailpoet_badge mailpoet_badge_video mailpoet_welcome_wizard_video_badge">
        <span className="dashicons dashicons-format-video" />
        {MailPoet.I18n.t('seeVideoGuide')}
      </span>
      <p>
        {
          ReactStringReplace(
            MailPoet.I18n.t('welcomeWizardAboutVideosText'),
            /\[strong\](.*?)\[\/strong\]/g,
            match => (<strong key={match}>{ match }</strong>)
          )
        }
      </p>
    </div>
    <div className="mailpoet_welcome_wizard_help_info_block">
      <span className="mailpoet_welcome_wizard_mail_icon" />
      <p>
        {
          ReactStringReplace(
            MailPoet.I18n.t('welcomeWizardAboutCourseText'),
            /\[strong\](.*?)\[\/strong\]/g,
            match => (<strong key={match}>{ match }</strong>)
          )
        }
      </p>
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
        allowTransparency="true"
      />
    </div>
    <button className="button button-primary" onClick={props.next}>{MailPoet.I18n.t('next')}</button>
  </div>
);

module.exports = WelcomeWizardHelpInfoStep;

WelcomeWizardHelpInfoStep.propTypes = {
  next: React.PropTypes.func.isRequired,
};

module.exports = WelcomeWizardHelpInfoStep;
