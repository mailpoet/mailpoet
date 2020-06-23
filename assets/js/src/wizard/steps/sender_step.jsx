import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import jQuery from 'jquery';
import Button from '../../common/button/button';
import Grid from '../../common/grid';
import Heading from '../../common/typography/heading/heading';
import Input from '../../common/form/input/input';

const WelcomeWizardSenderStep = (props) => (
  <>
    <Heading level={1}>{MailPoet.I18n.t('welcomeWizardLetsStartTitle')}</Heading>

    <div className="mailpoet-gap" />
    <p>{MailPoet.I18n.t('welcomeWizardSenderText')}</p>
    <div className="mailpoet-gap" />

    <form
      id="mailpoet_sender_form"
      onSubmit={(e) => {
        e.preventDefault();
        if (!jQuery('#mailpoet_sender_form').parsley().validate()) { return; }
        props.submit_sender();
      }}
    >
      <Grid.SpaceBetween>
        <Grid.Column dimension="small">
          <label htmlFor="senderName">
            <span className="mailpoet-wizard-label">{MailPoet.I18n.t('senderName')}</span>
            <Input
              name="senderName"
              type="text"
              placeholder="John Doe"
              value={props.sender ? props.sender.name : ''}
              data-parsley-required
              onChange={(e) => props.update_sender({ name: e.target.value })}
            />
          </label>
        </Grid.Column>
        <Grid.Column>
          <label htmlFor="senderAddress">
            <span className="mailpoet-wizard-label">{MailPoet.I18n.t('senderAddress')}</span>
            <Input
              name="senderAddress"
              type="text"
              placeholder="john@doe.com"
              value={props.sender ? props.sender.address : ''}
              data-parsley-required
              data-parsley-type="email"
              onChange={(e) => props.update_sender({ address: e.target.value })}
            />
          </label>
        </Grid.Column>
      </Grid.SpaceBetween>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <Button
        isFullWidth
        type="submit"
        withSpinner={props.loading}
      >
        {MailPoet.I18n.t('continue')}
      </Button>
      <Button
        href="#finish"
        isDisabled={props.loading}
        isFullWidth
        onClick={props.finish}
        variant="link"
      >
        {MailPoet.I18n.t('finishLater')}
      </Button>
    </form>
  </>
);

WelcomeWizardSenderStep.propTypes = {
  finish: PropTypes.func.isRequired,
  loading: PropTypes.bool.isRequired,
  update_sender: PropTypes.func.isRequired,
  submit_sender: PropTypes.func.isRequired,
  sender: PropTypes.shape({
    name: PropTypes.string,
    address: PropTypes.string,
  }),
};

WelcomeWizardSenderStep.defaultProps = {
  sender: null,
};

export default WelcomeWizardSenderStep;
