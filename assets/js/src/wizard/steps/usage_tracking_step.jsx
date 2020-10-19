import PropTypes from 'prop-types';
import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';
import Grid from 'common/grid';
import Heading from 'common/typography/heading/heading';
import List from 'common/typography/list/list';
import YesNo from 'common/form/yesno/yesno';

const WelcomeWizardUsageTrackingStep = (props) => {
  const [trackingEnabled, setTrackingEnabled] = useState(true);
  const [libs3rdPartyEnabled, setLibs3rdPartyEnabled] = useState(true);
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
              onCheck={setTrackingEnabled}
              checked={trackingEnabled}
              name="mailpoet_tracking"
            />
          </div>
          <div>
            <p>
              {MailPoet.I18n.t('welcomeWizardUsageTrackingStepTrackingLabel')}
              {' '}
              <a
                href="https://kb.mailpoet.com/article/130-sharing-your-data-with-us"
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

        <div className="mailpoet-wizard-woocommerce-option">
          <div className="mailpoet-wizard-woocommerce-toggle">
            <YesNo
              onCheck={setLibs3rdPartyEnabled}
              checked={libs3rdPartyEnabled}
              name="mailpoet_libs_3rdParty"
            />
          </div>
          <div>
            <p>
              {MailPoet.I18n.t('welcomeWizardUsageTrackingStepLibs3rdPartyLabel')}
              {' '}
              <a
                href="https://kb.mailpoet.com/article/338-what-3rd-party-libraries-we-use"
                data-beacon-article="5f7c7dd94cedfd0017dcece8"
                target="_blank"
                rel="noopener noreferrer"
              >
                {MailPoet.I18n.t('welcomeWizardUsageTrackingStepLibs3rdPartyLink')}
              </a>
            </p>
            <div className="mailpoet-wizard-note">
              <span>{MailPoet.I18n.t('welcomeWizardUsageTrackingStepLibs3rdPartyLabelNoteNote')}</span>
              {MailPoet.I18n.t('welcomeWizardUsageTrackingStepLibs3rdPartyLabelNote')}
            </div>
          </div>
        </div>

        <div className="mailpoet-gap" />
        <Button
          isFullWidth
          type="submit"
          withSpinner={props.loading}
          disabled={props.loading}
        >
          {MailPoet.I18n.t('continue')}
        </Button>
      </form>
    </>
  );
};

WelcomeWizardUsageTrackingStep.propTypes = {
  loading: PropTypes.bool.isRequired,
};

export default WelcomeWizardUsageTrackingStep;
