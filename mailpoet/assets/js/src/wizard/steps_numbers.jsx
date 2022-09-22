export const getStepsCount = () => {
  let stepsCount = 2;
  if (!window.has_mss_key_specified) {
    stepsCount += 1;
  }
  if (window.mailpoet_woocommerce_active) {
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
  if (stepNumber === 1) {
    return 'WelcomeWizardSenderStep';
  }
  if (stepNumber === 2) {
    return 'WelcomeWizardUsageTrackingStep';
  }
  if (window.mailpoet_woocommerce_active && stepNumber === getStepsCount()) {
    return 'WizardWooCommerceStep';
  }
  return 'WelcomeWizardPitchMSSStep';
};
