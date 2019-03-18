import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import jQuery from 'jquery';

import SenderEmailAddressWarning from 'common/sender_email_address_warning.jsx';

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
        {MailPoet.I18n.t('senderName')}
        :
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
        {MailPoet.I18n.t('senderAddress')}
        :
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
      <label htmlFor="replyToAddress">
        {MailPoet.I18n.t('replyToAddress')}
        :
        <input
          name="replyToAddress"
          type="text"
          placeholder="john@doe.com"
          value={props.reply_to ? props.reply_to.address : ''}
          data-parsley-required
          data-parsley-type="email"
          onChange={e => props.update_reply_to({ address: e.target.value })}
        />
      </label>
      <SenderEmailAddressWarning
        emailAddress={props.sender.address}
        mssActive={props.mssActive}
        pluginInstalledAt={props.pluginInstalledAt}
      />
      <input className="button button-primary" type="submit" value={MailPoet.I18n.t('next')} />
      <a onClick={props.finish} href="#finish" className="sender_form_small">{MailPoet.I18n.t('noThanksSkip')}</a>
    </form>
  </div>
);

WelcomeWizardSenderStep.propTypes = {
  finish: PropTypes.func.isRequired,
  loading: PropTypes.bool.isRequired,
  update_sender: PropTypes.func.isRequired,
  submit_sender: PropTypes.func.isRequired,
  update_reply_to: PropTypes.func.isRequired,
  sender: PropTypes.shape({
    name: PropTypes.string,
    address: PropTypes.string,
  }),
  reply_to: PropTypes.shape({
    address: PropTypes.string,
  }),
  mssActive: PropTypes.bool.isRequired,
  pluginInstalledAt: PropTypes.string.isRequired,
};

WelcomeWizardSenderStep.defaultProps = {
  sender: null,
  reply_to: null,
};

export default WelcomeWizardSenderStep;
