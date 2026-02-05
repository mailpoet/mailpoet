import { select } from '@wordpress/data';
import { Step } from '../../../../../editor/components/automation/types';
import { storeName } from '../../../../../editor/store';

const transactionalTriggers = [
  'mailpoet:custom-trigger',
  'woocommerce:order-status-changed',
  'woocommerce:order-created',
  'woocommerce:order-completed',
  'woocommerce:order-cancelled',
  'woocommerce:abandoned-cart',
  'woocommerce-subscriptions:subscription-created',
  'woocommerce-subscriptions:subscription-expired',
  'woocommerce-subscriptions:subscription-payment-failed',
  'woocommerce-subscriptions:subscription-renewed',
  'woocommerce-subscriptions:subscription-status-changed',
  'woocommerce-subscriptions:trial-ended',
  'woocommerce-subscriptions:trial-started',
  'woocommerce:buys-from-a-tag',
  'woocommerce:buys-from-a-category',
  'woocommerce:buys-a-product',
  'woocommerce-bookings:booking-created',
  'woocommerce-bookings:booking-status-changed',
];

// Only the delay action converts an email from transactional to marketing.
// Other actions (if/else, tagging, adding to list) do not affect transactional nature.
const delayActionKey = 'core:delay';

/**
 * Checks if there exists at least one path from the 'from' step to the 'to' step
 * that doesn't contain a delay action.
 */
function hasDelayFreePathToStep(
  from: Step,
  to: Step,
  steps: Record<string, Step>,
): boolean {
  const stack: Array<[Step, boolean]> = [[from, false]]; // [step, hasDelayOnPath]
  const visited: Record<string, boolean> = {};

  while (stack.length > 0) {
    const item = stack.pop();
    if (!item) {
      break;
    }
    const [current, hasDelayOnPath] = item;
    const currentHasDelay = current.key === delayActionKey || hasDelayOnPath;

    const stateKey = `${current.id}:${currentHasDelay ? '1' : '0'}`;
    if (visited[stateKey]) {
      // eslint-disable-next-line no-continue
      continue;
    }
    visited[stateKey] = true;

    if (current.id === to.id && !currentHasDelay) {
      return true;
    }

    if (current.id !== to.id) {
      current.next_steps.forEach((nextStep) => {
        const nextStepObj = steps[nextStep.id];
        if (nextStepObj) {
          stack.push([nextStepObj, currentHasDelay]);
        }
      });
    }
  }
  return false;
}

export function isTransactional(step: Step): boolean {
  const automation = select(storeName).getAutomationData();
  const { steps } = automation;
  const allSteps: Step[] = Object.values(steps);
  const triggers = allSteps.filter((s) => s.type === 'trigger');

  // All triggers must be transactional triggers
  const transactionalTriggersOnly = triggers.every((trigger) =>
    transactionalTriggers.includes(trigger.key),
  );

  if (!triggers.length || !transactionalTriggersOnly) {
    return false;
  }

  // Every transactional trigger must have at least one delay-free path to the email step
  const allTriggersHaveDelayFreePath = triggers.every((trigger) =>
    hasDelayFreePathToStep(trigger, step, steps as Record<string, Step>),
  );

  return allTriggersHaveDelayFreePath;
}
