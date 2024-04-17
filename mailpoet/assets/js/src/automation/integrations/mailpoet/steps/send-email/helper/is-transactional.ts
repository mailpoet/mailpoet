import { select } from '@wordpress/data';
import { Step } from '../../../../../editor/components/automation/types';
import { storeName } from '../../../../../editor/store';

const transactionalTriggers = [
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
];

export function isTransactional(step: Step): boolean {
  const automation = select(storeName).getAutomationData();
  const triggers = Object.values(automation.steps).filter(
    (s) => s.type === 'trigger',
  );

  let triggersAllowTransactionals = null;
  triggers.forEach((trigger) => {
    if (
      triggersAllowTransactionals === true &&
      !transactionalTriggers.includes(trigger.key)
    ) {
      triggersAllowTransactionals = false;
    }
    if (
      triggersAllowTransactionals === null &&
      transactionalTriggers.includes(trigger.key)
    ) {
      triggersAllowTransactionals = true;
    }
  });

  if (!triggersAllowTransactionals) {
    return false;
  }

  let stepPositionIsUnderNeathTransactionalTrigger = false;
  triggers.forEach((trigger) => {
    if (trigger.next_steps.map((next) => next.id).includes(step.id)) {
      stepPositionIsUnderNeathTransactionalTrigger = true;
    }
  });
  return stepPositionIsUnderNeathTransactionalTrigger;
}
