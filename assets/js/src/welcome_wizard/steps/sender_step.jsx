import React from 'react';
import MailPoet from 'mailpoet';
import jQuery from 'jquery';

const WelcomeWizardSenderStep = props => (
  <div className="mailpoet_welcome_wizard_step_content mailpoet_welcome_wizard_centered_column">
    <h1>{MailPoet.I18n.t('welcomeWizardLetsStartTitle')}</h1>
    <p>{MailPoet.I18n.t('welcomeWizardSenderText')}</p>
    <form
      id="mailpoet_sender_form"
      className={
        `mailpoet_welcome_wizard_centered_column ${(props.loading ? 'mailpoet_sender_form_loading' : '')}`
      }
      onSubmit={(e) => {
        e.preventDefault();
        if (!jQuery('#mailpoet_sender_form').parsley().validate()) { return; }
        props.submit_sender();
      }}
    >
      <label htmlFor="senderName">
        {MailPoet.I18n.t('senderName')}:
        <input
          name="senderName"
          type="text"
          placeholder="John Doe"
          value={props.sender ? props.sender.name : ''}
          data-parsley-required
          onChange={e => props.update_sender({ name: e.target.value })}
        />
      </label>
      <label htmlFor="senderAddress">
        {MailPoet.I18n.t('senderAddress')}:
        <input
          name="senderAddress"
          type="text"
          placeholder="john@doe.com"
          value={props.sender ? props.sender.address : ''}
          data-parsley-required
          data-parsley-type="email"
          onChange={e => props.update_sender({ address: e.target.value })}
        />
      </label>
      <input className="button button-primary" type="submit" value={MailPoet.I18n.t('next')} />
      <a onClick={props.finish} href="#finish">{MailPoet.I18n.t('noThanksSkip')}</a>
    </form>
  </div>
);

WelcomeWizardSenderStep.propTypes = {
  finish: React.PropTypes.func.isRequired,
  loading: React.PropTypes.bool.isRequired,
  update_sender: React.PropTypes.func.isRequired,
  submit_sender: React.PropTypes.func.isRequired,
  sender: React.PropTypes.shape({
    name: React.PropTypes.string,
    address: React.PropTypes.string,
  }),
};

WelcomeWizardSenderStep.defaultProps = {
  sender: null,
};

module.exports = WelcomeWizardSenderStep;
