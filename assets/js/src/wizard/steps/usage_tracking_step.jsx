import PropTypes from 'prop-types';
import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import Button from '../../common/button/button';
import Grid from '../../common/grid';
import Heading from '../../common/typography/heading/heading';
import List from '../../common/typography/list/list';
import YesNo from '../../common/form/yesno/yesno';

const WelcomeWizardUsageTrackingStep = (props) => {
  const [trackingEnabled, setTrackingEnabled] = useState(true);
  function submit() {
    return false;
  }
  return (
    <>
      <Heading level={1}>{MailPoet.I18n.t('welcomeWizardUsageTrackingStepTitle')}</Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardTrackingText')}</p>
      <div className="mailpoet-gap" />

      <Heading level={5}>{MailPoet.I18n.t('welcomeWizardUsageTrackingStepSubTitle')}</Heading>
      <Grid.TwoColumnsList>
        <List>
          <li>{MailPoet.I18n.t('welcomeWizardTrackingList1')}</li>
          <li>{MailPoet.I18n.t('welcomeWizardTrackingList2')}</li>
          <li>{MailPoet.I18n.t('welcomeWizardTrackingList3')}</li>
          <li>{MailPoet.I18n.t('welcomeWizardTrackingList4')}</li>
          <li>{MailPoet.I18n.t('welcomeWizardTrackingList5')}</li>
        </List>
      </Grid.TwoColumnsList>

      <div className="mailpoet-gap" />

      <form onSubmit={submit}>
        <div className="mailpoet-wizard-woocommerce-option">
          <div className="mailpoet-wizard-woocommerce-toggle">
            <YesNo
              onCheck={(value) => setTrackingEnabled(value)}
              checked={trackingEnabled}
              name="mailpoet_tracking"
              automationId="tracking"
            />
          </div>
          <div>
            <p>
              {MailPoet.I18n.t('welcomeWizardUsageTrackingStepTrackingLabel')}
              {' '}
              <a
                href=" https://kb.mailpoet.com/article/130-sharing-your-data-with-us"
                data-beacon-article="57ce0aaac6979108399a0454"
                target="_blank"
                rel="noopener noreferrer"
              >
                {MailPoet.I18n.t('welcomeWizardTrackingLink')}
              </a>
            </p>
            <div className="mailpoet-wizard-note">
              <span>{MailPoet.I18n.t('welcomeWizardUsageTrackingStepTrackingLabelNoteNote')}</span>
              {MailPoet.I18n.t('welcomeWizardUsageTrackingStepTrackingLabelNote')}
            </div>
          </div>
        </div>

        <div className="mailpoet-gap" />
        <Button
          isFullWidth
          onClick={props.allow_action}
          withSpinner={props.loading}
        >
          {props.allow_text}
        </Button>
      </form>
    </>
  );
};

WelcomeWizardUsageTrackingStep.propTypes = {
  allow_action: PropTypes.func.isRequired,
  allow_text: PropTypes.string.isRequired,
  skip_action: PropTypes.func.isRequired,
  loading: PropTypes.bool.isRequired,
};

export default WelcomeWizardUsageTrackingStep;
