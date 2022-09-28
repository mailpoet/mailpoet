import { ReactNode } from 'react';
import { Location } from 'history';
import { MailPoet } from 'mailpoet';
import { Icon, video } from '@wordpress/icons';
import { HideScreenOptions } from '../../common/hide_screen_options/hide_screen_options';
import { MailPoetLogoResponsive } from '../../common/top_bar/mailpoet_logo_responsive';
import { Steps } from '../../common/steps/steps';
import { displayTutorial } from '../../newsletter_editor/tutorial';

export const mapPathToSteps = (
  location: Location,
  emailType?: string,
): number | null => {
  const stepsMap = [
    ['/new/.+', 1],
    ['/template/.+', emailType === 'automation' ? 1 : 2],
    ['/send/.+', 4],
  ];

  if (location.search.match(/page=mailpoet-newsletter-editor/g)) {
    return emailType === 'automation' ? 2 : 3;
  }

  let stepNumber = null;

  stepsMap.forEach(([regex, step]) => {
    if (
      new RegExp(`^#${regex}`).exec(location.hash) ||
      new RegExp(`^${regex}`).exec(location.pathname)
    ) {
      stepNumber = step;
    }
  });

  return stepNumber;
};

const getEmailTypeTitle = (emailType: string): string => {
  const typeMap = {
    standard: MailPoet.I18n.t('stepNameTypeStandard'),
    welcome: MailPoet.I18n.t('stepNameTypeWelcome'),
    notification: MailPoet.I18n.t('stepNameTypeNotification'),
    woocommerce: MailPoet.I18n.t('stepNameTypeWooCommerce'),
    re_engagement: MailPoet.I18n.t('stepNameTypeReEngagement'),
  };

  return typeMap[emailType] || MailPoet.I18n.t('stepNameTypeStandard');
};

const getEmailSendTitle = (emailType: string): string => {
  const typeMap = {
    standard: MailPoet.I18n.t('stepNameSend'),
    welcome: MailPoet.I18n.t('stepNameActivate'),
    notification: MailPoet.I18n.t('stepNameActivate'),
    woocommerce: MailPoet.I18n.t('stepNameActivate'),
    re_engagement: MailPoet.I18n.t('stepNameActivate'),
  };

  return typeMap[emailType] || MailPoet.I18n.t('stepNameSend');
};

function TutorialIcon(): JSX.Element {
  return (
    <div>
      <a
        role="button"
        onClick={displayTutorial}
        className="mailpoet-top-bar-beamer"
        title={MailPoet.I18n.t('topBarTutorial')}
        tabIndex={0}
        onKeyDown={(event) => {
          if (
            ['keydown', 'keypress'].includes(event.type) &&
            ['Enter', ' '].includes(event.key)
          ) {
            event.preventDefault();
            displayTutorial();
          }
        }}
      >
        <Icon icon={video} />
        <span>{MailPoet.I18n.t('topBarTutorial')}</span>
      </a>
      <span id="beamer-empty-element" />
    </div>
  );
}

const stepsListingHeading = (
  step: number,
  emailType: string,
  automationId: string,
  buttons: ReactNode,
  onLogoClick?: () => void,
): JSX.Element => {
  const emailTypeTitle = getEmailTypeTitle(emailType);
  let stepTitles = [
    emailTypeTitle,
    MailPoet.I18n.t('stepNameTemplate'),
    MailPoet.I18n.t('stepNameDesign'),
    getEmailSendTitle(emailType),
  ];
  // Automation email has only 2 steps
  if (emailType === 'automation') {
    stepTitles = [
      MailPoet.I18n.t('stepNameTemplate'),
      MailPoet.I18n.t('stepNameDesign'),
    ];
  }

  return (
    <div className="mailpoet-top-bar" data-automation-id={automationId}>
      <MailPoetLogoResponsive onClick={onLogoClick} />
      <HideScreenOptions />
      <Steps count={stepTitles.length} current={step} titles={stepTitles} />
      {buttons && (
        <div className="mailpoet-newsletter-listing-heading-buttons">
          {buttons}
        </div>
      )}
      <h1 className="mailpoet-newsletter-listing-heading title mailpoet_hidden">
        {' '}
      </h1>
      <div className="mailpoet-flex-grow" />
      {emailType !== 'automation' && <TutorialIcon />}
    </div>
  );
};

export interface Props {
  step?: number;
  emailType?: string;
  automationId?: string;
  location: Location;
  onLogoClick?: () => void;
  buttons?: ReactNode;
}

function ListingHeadingSteps({
  step,
  emailType,
  location,
  automationId,
  buttons,
  onLogoClick,
}: Props): JSX.Element {
  const stepNumber = step || mapPathToSteps(location, emailType);

  if (stepNumber !== null) {
    return stepsListingHeading(
      stepNumber,
      emailType,
      automationId,
      buttons,
      onLogoClick,
    );
  }
  return null;
}

export { ListingHeadingSteps };
