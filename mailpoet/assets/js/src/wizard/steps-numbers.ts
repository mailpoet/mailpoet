export const getStepsCount = (): number => {
  let stepsCount = 3;
  if (window.mailpoet_woocommerce_active) {
    stepsCount += 1;
  }
  if (window.mailpoet_has_valid_api_key) {
    stepsCount -= 1; // skip the MSS step if user already set API key
  }
  return stepsCount;
};

export const redirectToNextStep = (
  history: { push: (string) => void },
  finishWizard: () => void,
  currentStep: number,
): void => {
  const stepsCount = getStepsCount();
  if (currentStep < stepsCount) {
    history.push(`/steps/${currentStep + 1}`);
  } else {
    finishWizard();
  }
};

export const mapStepNumberToStepName = (stepNumber: number): string | null => {
  if (stepNumber === 1) {
    return 'WelcomeWizardSenderStep';
  }
  if (stepNumber === 2) {
    return 'WelcomeWizardUsageTrackingStep';
  }
  if (window.mailpoet_woocommerce_active && stepNumber === 3) {
    return 'WizardWooCommerceStep';
  }
  if (!window.mailpoet_has_valid_api_key) {
    return 'WelcomeWizardPitchMSSStep';
  }
  return null;
};
