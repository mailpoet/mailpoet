import { ReactNode } from 'react';
import { Location } from 'history';
import { Icon, video } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { HideScreenOptions } from '../../common/hide-screen-options/hide-screen-options';
import { MailPoetLogoResponsive } from '../../common/top-bar/mailpoet-logo-responsive';
import { Steps } from '../../common/steps/steps';
import { displayTutorial } from '../../newsletter-editor/tutorial';
import { automationTypes } from './utils';

export const mapPathToSteps = (
  location: Location,
  emailType?: string,
): number | null => {
  const stepsMap = [
    ['/new/.+', 1],
    ['/template/.+', automationTypes.includes(emailType) ? 1 : 2],
    ['/send/.+', 4],
  ];

  if (location.search.match(/page=mailpoet-newsletter-editor/g)) {
    return automationTypes.includes(emailType) ? 2 : 3;
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
    standard: __('Newsletter', 'mailpoet'),
    welcome: __('Welcome Email', 'mailpoet'),
    notification: __('Post Notification', 'mailpoet'),
    woocommerce: __('WooCommerce', 'mailpoet'),
    re_engagement: __('Re-engagement', 'mailpoet'),
  };

  return typeMap[emailType] || __('Newsletter', 'mailpoet');
};

const getEmailSendTitle = (emailType: string): string => {
  const typeMap = {
    standard: __('Send', 'mailpoet'),
    welcome: __('Activate', 'mailpoet'),
    notification: __('Activate', 'mailpoet'),
    woocommerce: __('Activate', 'mailpoet'),
    re_engagement: __('Activate', 'mailpoet'),
  };

  return typeMap[emailType] || __('Send', 'mailpoet');
};

function TutorialIcon(): JSX.Element {
  return (
    <div>
      <a
        role="button"
        onClick={displayTutorial}
        className="mailpoet-top-bar-beamer"
        title={__('Tutorial', 'mailpoet')}
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
        <span>{__('Tutorial', 'mailpoet')}</span>
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
    __('Template', 'mailpoet'),
    __('Design', 'mailpoet'),
    getEmailSendTitle(emailType),
  ];
  // Automation email has only 2 steps
  if (automationTypes.includes(emailType)) {
    stepTitles = [__('Template', 'mailpoet'), __('Design', 'mailpoet')];
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
      {!automationTypes.includes(emailType) && step === 3 && <TutorialIcon />}
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

ListingHeadingSteps.displayName = 'ListingHeadingSteps';
export { ListingHeadingSteps };
