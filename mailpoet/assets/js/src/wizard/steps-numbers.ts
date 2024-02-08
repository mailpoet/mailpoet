import { History } from 'history';
import { updateSettings } from './update-settings';

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

export const navigateToPath = async (history: History, path: string, replaceCurrent = false) => {
  await updateSettings({ welcome_wizard_current_step: path });
  if (replaceCurrent) {
    history.replace(path);
  } else {
    history.push(path);
  }
}

export const redirectToNextStep = async (
  history: History,
  finishWizard: () => void,
  currentStep: number,
) => {
  const stepsCount = getStepsCount();
  if (currentStep < stepsCount) {
    const nextPath = `/steps/${currentStep + 1}`;
    await navigateToPath(history, nextPath);
  } else {
    finishWizard();
  }
};

