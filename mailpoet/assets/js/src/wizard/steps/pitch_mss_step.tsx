import { MailPoet } from 'mailpoet';
import { Button } from '../../common';
import { Heading } from '../../common/typography/heading/heading';
import { List } from '../../common/typography/list/list';

export function FreeBenefitsList(): JSX.Element {
  return (
    <List>
      <li>{MailPoet.I18n.t('welcomeWizardMSSList1')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardMSSList2')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardMSSList4')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardMSSList5')}</li>
    </List>
  );
}

export function NotFreeBenefitsList(): JSX.Element {
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

type ControlsPropType = {
  mailpoetAccountUrl: string;
  next: () => void;
  nextButtonText: string;
  nextWithSpinner?: boolean;
};

export function Controls({
  mailpoetAccountUrl,
  next,
  nextButtonText,
  nextWithSpinner = false,
}: ControlsPropType): JSX.Element {
  return (
    <>
      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <Button
        isFullWidth
        href={mailpoetAccountUrl}
        target="_blank"
        rel="noopener noreferrer"
        onClick={(event) => {
          event.preventDefault();
          window.open(mailpoetAccountUrl);
          next();
        }}
      >
        {nextButtonText}
      </Button>
      <Button
        isFullWidth
        variant="tertiary"
        onClick={next}
        onKeyDown={(event) => {
          if (
            ['keydown', 'keypress'].includes(event.type) &&
            ['Enter', ' '].includes(event.key)
          ) {
            event.preventDefault();
            next();
          }
        }}
        withSpinner={nextWithSpinner}
      >
        {MailPoet.I18n.t('welcomeWizardMSSNoThanks')}
      </Button>
    </>
  );
}

type FreePlanSubscribersPropType = {
  next: () => void;
};

function FreePlanSubscribers({
  next,
}: FreePlanSubscribersPropType): JSX.Element {
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
        next={next}
        nextButtonText={MailPoet.I18n.t('welcomeWizardMSSFreeButton')}
      />
    </>
  );
}

FreePlanSubscribers.displayName = 'FreePlanSubscribers';

type NotFreePlanSubscribersPropType = {
  mailpoetAccountUrl: string;
  next: () => void;
};

function NotFreePlanSubscribers({
  mailpoetAccountUrl,
  next,
}: NotFreePlanSubscribersPropType): JSX.Element {
  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardMSSNotFreeTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardMSSNotFreeSubtitle')}:</p>
      <NotFreeBenefitsList />

      <Controls
        mailpoetAccountUrl={mailpoetAccountUrl}
        next={next}
        nextButtonText={MailPoet.I18n.t('welcomeWizardMSSNotFreeButton')}
      />
    </>
  );
}

NotFreePlanSubscribers.displayName = 'NotFreePlanSubscribers';

type WelcomeWizardPitchMSSStepPropType = {
  subscribersCount: number;
  next: () => void;
  purchaseUrl: string;
};

function WelcomeWizardPitchMSSStep({
  subscribersCount,
  next,
  purchaseUrl,
}: WelcomeWizardPitchMSSStepPropType): JSX.Element {
  return subscribersCount < 1000 ? (
    <FreePlanSubscribers next={next} />
  ) : (
    <NotFreePlanSubscribers mailpoetAccountUrl={purchaseUrl} next={next} />
  );
}

export { WelcomeWizardPitchMSSStep };
