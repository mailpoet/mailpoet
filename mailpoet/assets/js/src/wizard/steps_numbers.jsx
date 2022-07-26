export const getStepsCount = () => {
  let stepsCount = 2;
  if (!window.has_mss_key_specified) {
    stepsCount += 1;
  }
  if (window.is_woocommerce_active) {
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
  if (stepNumber === 1 && shouldSetSender) {
    return 'WelcomeWizardSenderStep';
  }
  if (stepNumber === 1 && !shouldSetSender) {
    return 'WelcomeWizardMigratedUserStep';
  }
  if (stepNumber === 2) {
    return 'WelcomeWizardUsageTrackingStep';
  }
  if (window.is_woocommerce_active && stepNumber === getStepsCount()) {
    return 'WizardWooCommerceStep';
  }
  return 'WelcomeWizardPitchMSSStep';
};
