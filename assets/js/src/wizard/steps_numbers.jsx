import MailPoet from 'mailpoet';

export const getStepsCount = () => {
  let stepsCount = 3;
  if (window.is_woocommerce_active) {
    stepsCount += 1;
  }
  if (!window.has_premium_key && MailPoet.FeaturesController.isSupported('display-mss-pitch')) {
    stepsCount += 1;
  }
  return stepsCount;
};

export const redirectToNextStep = (history, finishWizard, currentStep) => {
  const stepsCount = getStepsCount();
  if (currentStep < stepsCount) {
    history.push(`/steps/${currentStep + 1}`);
  } else {
    finishWizard();
  }
};

export const mapStepNumberToStepName = (stepNumber) => {
  const shouldSetSender = !window.is_mp2_migration_complete;
  const isWoocommerceActive = window.is_woocommerce_active;
  if (stepNumber === 1 && shouldSetSender) {
    return 'WelcomeWizardSenderStep';
  }
  if (stepNumber === 1 && !shouldSetSender) {
    return 'WelcomeWizardMigratedUserStep';
  }
  if (stepNumber === 2) {
    return 'WelcomeWizardEmailCourseStep';
  }
  if (stepNumber === 3) {
    return 'WelcomeWizardUsageTrackingStep';
  }
  if (stepNumber === 4 && isWoocommerceActive) {
    return 'WelcomeWizardWooCommerceStep';
  }
  return 'WelcomeWizardPitchMSSStep';
};
