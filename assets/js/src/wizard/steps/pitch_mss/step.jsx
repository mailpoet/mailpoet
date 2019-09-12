import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const BenefitsList = () => (
  <ul className="welcome_wizard_tracking_list">
    <li>{MailPoet.I18n.t('welcomeWizardMSSList1')}</li>
    <li>{MailPoet.I18n.t('welcomeWizardMSSList2')}</li>
    <li>{MailPoet.I18n.t('welcomeWizardMSSList3WooCommerce')}</li>
    <li>{MailPoet.I18n.t('welcomeWizardMSSList4')}</li>
    <li>{MailPoet.I18n.t('welcomeWizardMSSList5')}</li>
  </ul>
);

const FreePlanSubscribers = () => (
  <>
    <h1>{MailPoet.I18n.t('welcomeWizardMSSFreeTitle')}</h1>
    <p>{MailPoet.I18n.t('welcomeWizardMSSFreeSubtitle')}</p>
    <p>
      {MailPoet.I18n.t('welcomeWizardMSSFreeListTitle')}
      :
    </p>
    <BenefitsList />
    <a
      href="https://account.mailpoet.com/?s={number_of_subscribers}"
      target="_blank"
      rel="noopener noreferrer"
      className="button button-primary"
    >
      {MailPoet.I18n.t('welcomeWizardMSSFreeButton')}
    </a>
  </>
);

const NotFreePlanSubscribers = () => (
  <>
    <h1>{MailPoet.I18n.t('welcomeWizardMSSNotFreeTitle')}</h1>
    <p>
      {MailPoet.I18n.t('welcomeWizardMSSNotFreeSubtitle')}
      :
    </p>
    <p>
      {MailPoet.I18n.t('welcomeWizardMSSFreeListTitle')}
      :
    </p>
    <BenefitsList />
    <a
      href="https://account.mailpoet.com/?s={number_of_subscribers}"
      target="_blank"
      rel="noopener noreferrer"
      className="button button-primary"
    >
      {MailPoet.I18n.t('welcomeWizardMSSNotFreeButton')}
    </a>
  </>
);

const Step = (props) => (
  <div className="mailpoet_welcome_wizard_step_content">
    { props.subscribersCount < 1000
      ? (
        <FreePlanSubscribers />
      ) : (
        <NotFreePlanSubscribers />
      )
    }
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

Step.propTypes = {
  next: PropTypes.func.isRequired,
  subscribersCount: PropTypes.number.isRequired,
};

export default Step;
