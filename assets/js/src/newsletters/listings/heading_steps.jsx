import React from 'react';
import MailPoet from 'mailpoet';
import HideScreenOptions from '../../common/hide_screen_options/hide_screen_options.tsx';
import Steps from '../../common/steps/steps.tsx';

export const mapPathToSteps = (location) => {
  const stepsMap = [
    ['/new/.+', 1],
    ['/template/.+', 2],
    ['/send/.+', 4],
  ];

  if (location.search.match(/page=mailpoet-newsletter-editor/)) {
    return 3;
  }

  let stepNumber = null;

  stepsMap.forEach(([regex, step]) => {
    if (location.hash.match(new RegExp(`^#${regex}`)) || location.pathname.match(new RegExp(`^${regex}`))) {
      stepNumber = step;
    }
  });

  return stepNumber;
};

const getEmailTypeTitle = (emailType) => {
  const typeMap = {
    standard: MailPoet.I18n.t('stepNameTypeStandard'),
    welcome: MailPoet.I18n.t('stepNameTypeWelcome'),
    notification: MailPoet.I18n.t('stepNameTypeNotification'),
    woocommerce: MailPoet.I18n.t('stepNameTypeWooCommerce'),
  };

  return typeMap[emailType] || MailPoet.I18n.t('stepNameTypeStandard');
};

const stepsListingHeading = (step, emailTypeTitle, automationId) => (
  <div className="mailpoet-newsletter-listing-heading-wrapper" data-automation-id={automationId}>
    <HideScreenOptions />
    <Steps count={4} current={step} titles={[emailTypeTitle, MailPoet.I18n.t('stepNameTemplate'), MailPoet.I18n.t('stepNameDesign'), MailPoet.I18n.t('stepNameSend')]} />
    <h1 className="mailpoet-newsletter-listing-heading title mailpoet_hidden">{' '}</h1>
  </div>
);

const ListingHeadingSteps = ({
  step,
  emailType,
  location,
  automationId,
}) => {
  const stepNumber = step || mapPathToSteps(location);
  const emailTypeTitle = getEmailTypeTitle(emailType);
  if (stepNumber !== null) {
    return stepsListingHeading(stepNumber, emailTypeTitle, automationId);
  }
  return null;
};

export default ListingHeadingSteps;
