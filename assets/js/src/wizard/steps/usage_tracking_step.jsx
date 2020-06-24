import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import Button from '../../common/button/button';
import Grid from '../../common/grid';
import Heading from '../../common/typography/heading/heading';
import List from '../../common/typography/list/list';

const WelcomeWizardUsageTrackingStep = (props) => (
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
    <p>
      <a
        href=" https://kb.mailpoet.com/article/130-sharing-your-data-with-us"
        data-beacon-article="57ce0aaac6979108399a0454"
        target="_blank"
        rel="noopener noreferrer"
      >
        {MailPoet.I18n.t('welcomeWizardTrackingLink')}
      </a>
    </p>

    <div className="mailpoet-gap" />
    <Button
      isFullWidth
      onClick={props.allow_action}
      withSpinner={props.loading}
    >
      {props.allow_text}
    </Button>
    <Button
      isDisabled={props.loading}
      isFullWidth
      onClick={props.skip_action}
      variant="link"
    >
      {MailPoet.I18n.t('skip')}
    </Button>
  </>
);

WelcomeWizardUsageTrackingStep.propTypes = {
  allow_action: PropTypes.func.isRequired,
  allow_text: PropTypes.string.isRequired,
  skip_action: PropTypes.func.isRequired,
  loading: PropTypes.bool.isRequired,
};

export default WelcomeWizardUsageTrackingStep;
