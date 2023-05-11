import { select } from '@wordpress/data';
import { storeName } from '../store';

export function automationHasEmails(): boolean {
  const automation = select(storeName).getAutomation();
  const emailSteps = Object.values(automation.steps).filter(
    (step) => step.key === 'mailpoet:send-email',
  );
  return emailSteps.length > 0;
}
