import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import Button from '../../common/button/button';
import Heading from '../../common/typography/heading/heading';
import List from '../../common/typography/list/list';

export const BenefitsList = () => (
  <List>
    <li>{MailPoet.I18n.t('welcomeWizardMSSList1')}</li>
    <li>{MailPoet.I18n.t('welcomeWizardMSSList2')}</li>
    <li>{MailPoet.I18n.t('welcomeWizardMSSList4')}</li>
    <li>{MailPoet.I18n.t('welcomeWizardMSSList5')}</li>
  </List>
);

export const Controls = (props) => (
  <>
    <div className="mailpoet-gap" />
    <div className="mailpoet-gap" />

    <Button
      isFullWidth
      href={props.mailpoetAccountUrl}
      target="_blank"
      rel="noopener noreferrer"
      onClick={(event) => {
        event.preventDefault();
        window.open(props.mailpoetAccountUrl);
        props.next();
      }}
    >
      {props.nextButtonText}
    </Button>
    <Button
      isFullWidth
      variant="link"
      onClick={props.next}
      onKeyDown={(event) => {
        if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
        ) {
          event.preventDefault();
          props.next();
        }
      }}
      withSpinner={props.nextWithSpinner}
    >
      {MailPoet.I18n.t('welcomeWizardMSSNoThanks')}
    </Button>
  </>
);

Controls.propTypes = {
  mailpoetAccountUrl: PropTypes.string.isRequired,
  next: PropTypes.func.isRequired,
  nextButtonText: PropTypes.string.isRequired,
  nextWithSpinner: PropTypes.bool,
};

Controls.defaultProps = {
  nextWithSpinner: false,
};

const FreePlanSubscribers = (props) => (
  <>
    <Heading level={1}>{MailPoet.I18n.t('welcomeWizardMSSFreeTitle')}</Heading>

    <div className="mailpoet-gap" />
    <p>{MailPoet.I18n.t('welcomeWizardMSSFreeSubtitle')}</p>
    <div className="mailpoet-gap" />

    <Heading level={5}>
      {MailPoet.I18n.t('welcomeWizardMSSFreeListTitle')}
      :
    </Heading>
    <BenefitsList />

    <Controls
      mailpoetAccountUrl={props.mailpoetAccountUrl}
      next={props.next}
      nextButtonText={MailPoet.I18n.t('welcomeWizardMSSFreeButton')}
    />
  </>
);

FreePlanSubscribers.propTypes = {
  mailpoetAccountUrl: PropTypes.string.isRequired,
  next: PropTypes.func.isRequired,
};

const NotFreePlanSubscribers = (props) => (
  <>
    <Heading level={1}>{MailPoet.I18n.t('welcomeWizardMSSNotFreeTitle')}</Heading>

    <div className="mailpoet-gap" />
    <p>
      {MailPoet.I18n.t('welcomeWizardMSSNotFreeSubtitle')}
      :
    </p>
    <BenefitsList />

    <Controls
      mailpoetAccountUrl={props.mailpoetAccountUrl}
      next={props.next}
      nextButtonText={MailPoet.I18n.t('welcomeWizardMSSNotFreeButton')}
    />
  </>
);

NotFreePlanSubscribers.propTypes = {
  mailpoetAccountUrl: PropTypes.string.isRequired,
  next: PropTypes.func.isRequired,
};

const Step = (props) => (
  <>
    { props.subscribersCount < 1000
      ? (
        <FreePlanSubscribers
          mailpoetAccountUrl={props.mailpoetAccountUrl}
          next={props.next}
        />
      ) : (
        <NotFreePlanSubscribers
          mailpoetAccountUrl={props.mailpoetAccountUrl}
          next={props.next}
        />
      )}
  </>
);

Step.propTypes = {
  next: PropTypes.func.isRequired,
  subscribersCount: PropTypes.number.isRequired,
  mailpoetAccountUrl: PropTypes.string.isRequired,
};

export default Step;
