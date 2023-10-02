import { select } from '@wordpress/data';
import { Step } from '../../../../../editor/components/automation/types';
import { storeName } from '../../../../../editor/store';

const transactionalTriggers = ['woocommerce:order-status-changed'];

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
