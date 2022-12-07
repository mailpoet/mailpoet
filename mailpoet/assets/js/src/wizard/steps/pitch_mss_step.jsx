import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { Button } from '../../common';
import { Heading } from '../../common/typography/heading/heading';
import { List } from '../../common/typography/list/list';

export function FreeBenefitsList() {
  return (
    <List>
      <li>{MailPoet.I18n.t('welcomeWizardMSSList1')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardMSSList2')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardMSSList4')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardMSSList5')}</li>
    </List>
  );
}

export function NotFreeBenefitsList() {
  return (
    <List>
      <li>{MailPoet.I18n.t('welcomeWizardMSSNotFreeList1')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardMSSNotFreeList2')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardMSSNotFreeList3')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardMSSNotFreeList4')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardMSSNotFreeList5')}</li>
    </List>
  );
}

export function Controls(props) {
  return (
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
        variant="tertiary"
        onClick={props.next}
        onKeyDown={(event) => {
          if (
            ['keydown', 'keypress'].includes(event.type) &&
            ['Enter', ' '].includes(event.key)
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
}

Controls.propTypes = {
  mailpoetAccountUrl: PropTypes.string.isRequired,
  next: PropTypes.func.isRequired,
  nextButtonText: PropTypes.string.isRequired,
  nextWithSpinner: PropTypes.bool,
};

Controls.defaultProps = {
  nextWithSpinner: false,
};

function FreePlanSubscribers(props) {
  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardMSSFreeTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardMSSFreeSubtitle')}</p>
      <div className="mailpoet-gap" />

      <Heading level={5}>
        {MailPoet.I18n.t('welcomeWizardMSSFreeListTitle')}:
      </Heading>
      <FreeBenefitsList />

      <Controls
        mailpoetAccountUrl={MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
          MailPoet.subscribersCount,
          MailPoet.currentWpUserEmail,
          'starter',
          { utm_medium: 'onboarding', utm_campaign: 'purchase' },
        )}
        next={props.next}
        nextButtonText={MailPoet.I18n.t('welcomeWizardMSSFreeButton')}
      />
    </>
  );
}

FreePlanSubscribers.propTypes = {
  next: PropTypes.func.isRequired,
};
FreePlanSubscribers.displayName = 'FreePlanSubscribers';

function NotFreePlanSubscribers(props) {
  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardMSSNotFreeTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardMSSNotFreeSubtitle')}:</p>
      <NotFreeBenefitsList />

      <Controls
        mailpoetAccountUrl={props.mailpoetAccountUrl}
        next={props.next}
        nextButtonText={MailPoet.I18n.t('welcomeWizardMSSNotFreeButton')}
      />
    </>
  );
}

NotFreePlanSubscribers.propTypes = {
  mailpoetAccountUrl: PropTypes.string.isRequired,
  next: PropTypes.func.isRequired,
};
NotFreePlanSubscribers.displayName = 'NotFreePlanSubscribers';

function WelcomeWizardPitchMSSStep(props) {
  return props.subscribersCount < 1000 ? (
    <FreePlanSubscribers
      mailpoetAccountUrl={props.mailpoetAccountUrl}
      next={props.next}
    />
  ) : (
    <NotFreePlanSubscribers
      mailpoetAccountUrl={props.purchaseUrl}
      next={props.next}
    />
  );
}

WelcomeWizardPitchMSSStep.propTypes = {
  next: PropTypes.func.isRequired,
  subscribersCount: PropTypes.number.isRequired,
  mailpoetAccountUrl: PropTypes.string.isRequired,
  purchaseUrl: PropTypes.string.isRequired,
};

export { WelcomeWizardPitchMSSStep };
