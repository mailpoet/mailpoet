import React from 'react';
import MailPoet from 'mailpoet';
import HideScreenOptions from '../../common/hide_screen_options/hide_screen_options';
import Steps from '../../common/steps/steps';

export const mapPathToSteps = (location: Location): number|null => {
  const stepsMap = [
    ['/new/.+', 1],
    ['/template/.+', 2],
    ['/send/.+', 4],
  ];

  if (location.search.match(/page=mailpoet-newsletter-editor/g)) {
    return 3;
  }

  let stepNumber = null;

  stepsMap.forEach(([regex, step]) => {
    if ((new RegExp(`^#${regex}`)).exec(location.hash) || (new RegExp(`^${regex}`)).exec(location.pathname)) {
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
  };

  return typeMap[emailType] || MailPoet.I18n.t('stepNameTypeStandard');
};

const getEmailSendTitle = (emailType: string): string => {
  const typeMap = {
    standard: MailPoet.I18n.t('stepNameSend'),
    welcome: MailPoet.I18n.t('stepNameActivate'),
    notification: MailPoet.I18n.t('stepNameActivate'),
    woocommerce: MailPoet.I18n.t('stepNameActivate'),
  };

  return typeMap[emailType] || MailPoet.I18n.t('stepNameSend');
};

const stepsListingHeading = (
  step: number,
  emailType: string,
  automationId: string
): JSX.Element => {
  const emailTypeTitle = getEmailTypeTitle(emailType);
  return (
    <div className="mailpoet-newsletter-listing-heading-wrapper" data-automation-id={automationId}>
      <HideScreenOptions />
      <Steps
        count={4}
        current={step}
        titles={[
          emailTypeTitle,
          MailPoet.I18n.t('stepNameTemplate'),
          MailPoet.I18n.t('stepNameDesign'),
          getEmailSendTitle(emailType),
        ]}
      />
      <h1 className="mailpoet-newsletter-listing-heading title mailpoet_hidden">{' '}</h1>
    </div>
  );
};

const ListingHeadingSteps = ({
  step,
  emailType,
  location,
  automationId,
}): JSX.Element => {
  const stepNumber = step || mapPathToSteps(location);
  if (stepNumber !== null) {
    return stepsListingHeading(stepNumber, emailType, automationId);
  }
  return null;
};

export default ListingHeadingSteps;
