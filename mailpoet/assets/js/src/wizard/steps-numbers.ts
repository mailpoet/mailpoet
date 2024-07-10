import { navigateToPath } from './navigate-to-path';

const getSteps = (): string[] => {
  const steps = ['WelcomeWizardSenderStep'];
  if (!window.mailpoet_is_dotcom) {
    steps.push('WelcomeWizardUsageTrackingStep');
  }
  if (window.mailpoet_woocommerce_active) {
    steps.push('WizardWooCommerceStep');
  }
  if (!window.mailpoet_has_valid_api_key) {
    steps.push('WelcomeWizardPitchMSSStep');
  }
  return steps;
};

export const getStepsCount = (): number => getSteps().length;

export const mapStepNumberToStepName = (stepNumber: number): string | null =>
  getSteps()[stepNumber - 1] || null;

export const redirectToNextStep = async (
  navigate,
  finishWizard: () => void,
  currentStep: number,
) => {
  const stepsCount = getStepsCount();
  if (currentStep < stepsCount) {
    const nextPath = `/steps/${currentStep + 1}`;
    navigateToPath(navigate, nextPath);
  } else {
    finishWizard();
  }
};
