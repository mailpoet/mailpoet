import { MailPoet } from 'mailpoet';
import { Icon, external } from '@wordpress/icons';
import ReactStringReplace from 'react-string-replace';
import { Button } from '../../common';
import { Heading } from '../../common/typography/heading/heading';
import { List } from '../../common/typography/list/list';

type ControlsPropType = {
  mailpoetAccountUrl: string;
  next: () => void;
  nextButtonText: string;
};

function Controls({
  mailpoetAccountUrl,
  next,
  nextButtonText,
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
        iconEnd={<Icon icon={external} />}
      >
        {nextButtonText}
      </Button>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />
    </>
  );
}

type WelcomeWizardPitchMSSStepPropType = {
  subscribersCount: number;
  next: () => void;
};

function WelcomeWizardPitchMSSStep({
  subscribersCount,
  next,
}: WelcomeWizardPitchMSSStepPropType): JSX.Element {
  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardMSSFreeTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardMSSFreeSubtitle')}</p>
      <div className="mailpoet-gap" />

      <List>
        <li>{MailPoet.I18n.t('welcomeWizardMSSList1')}</li>
        <li>{MailPoet.I18n.t('welcomeWizardMSSList2')}</li>
        {subscribersCount < 1000 ? (
          <li>{MailPoet.I18n.t('welcomeWizardMSSList3Free')}</li>
        ) : (
          <li>{MailPoet.I18n.t('welcomeWizardMSSList3Paid')}</li>
        )}
      </List>

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

      <p>
        {ReactStringReplace(
          MailPoet.I18n.t('welcomeWizardMSSAdvancedUsers'),
          /\[link](.*?)\[\/link]/g,
          (match) => (
            <a
              className="mailpoet-link"
              href="admin.php?page=mailpoet-settings#/mta/other"
            >
              {match}
            </a>
          ),
        )}
      </p>
    </>
  );
}

export { WelcomeWizardPitchMSSStep };
