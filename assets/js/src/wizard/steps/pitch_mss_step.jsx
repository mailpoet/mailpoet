import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const BenefitsList = () => (
  <ul className="welcome_wizard_tracking_list">
    <li>{MailPoet.I18n.t('welcomeWizardMSSList1')}</li>
    <li>{MailPoet.I18n.t('welcomeWizardMSSList2')}</li>
    <li>{MailPoet.I18n.t('welcomeWizardMSSList4')}</li>
    <li>{MailPoet.I18n.t('welcomeWizardMSSList5')}</li>
  </ul>
);

const Controls = (props) => (
  <div className="mailpoet_welcome_wizard_step_controls">
    <p>
      <a
        href={props.mailpoetAccountUrl}
        target="_blank"
        rel="noopener noreferrer"
        className="button button-primary"
        onClick={(event) => {
          event.preventDefault();
          window.open(props.mailpoetAccountUrl);
          props.next();
        }}
      >
        {props.nextButtonText}
      </a>
    </p>
    <p>
      <a
        onClick={props.next}
        role="button"
        tabIndex={0}
        onKeyDown={(event) => {
          if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
          ) {
            event.preventDefault();
            props.next();
          }
        }}
      >
        {MailPoet.I18n.t('welcomeWizardMSSNoThanks')}
      </a>
    </p>
  </div>
);

Controls.propTypes = {
  mailpoetAccountUrl: PropTypes.string.isRequired,
  next: PropTypes.func.isRequired,
  nextButtonText: PropTypes.string.isRequired,
};

const FreePlanSubscribers = (props) => (
  <>
    <h1>{MailPoet.I18n.t('welcomeWizardMSSFreeTitle')}</h1>
    <p>{MailPoet.I18n.t('welcomeWizardMSSFreeSubtitle')}</p>
    <p>
      {MailPoet.I18n.t('welcomeWizardMSSFreeListTitle')}
      :
    </p>
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
    <h>{MailPoet.I18n.t('welcomeWizardMSSNotFreeTitle')}</h>
    <p>
      {MailPoet.I18n.t('welcomeWizardMSSNotFreeSubtitle')}
      :
    </p>
    <p>
      {MailPoet.I18n.t('welcomeWizardMSSFreeListTitle')}
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
  <div className="mailpoet_welcome_wizard_step_content">
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
      )
    }
  </div>
);

Step.propTypes = {
  next: PropTypes.func.isRequired,
  subscribersCount: PropTypes.number.isRequired,
  mailpoetAccountUrl: PropTypes.string.isRequired,
};

export default Step;
