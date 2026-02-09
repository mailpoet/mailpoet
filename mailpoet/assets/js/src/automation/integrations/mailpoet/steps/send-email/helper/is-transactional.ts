import { select } from '@wordpress/data';
import { Step } from '../../../../../editor/components/automation/types';
import { storeName } from '../../../../../editor/store';
import { getContext } from '../../../context';

/**
 * Checks if there exists at least one path from the 'from' step to the 'to' step
 * that doesn't contain a delay action.
 */
function hasDelayFreePathToStep(
  from: Step,
  to: Step,
  steps: Record<string, Step>,
  delayActionKey: string,
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
  const context = getContext();
  const transactionalTriggers = context.transactional_triggers ?? [];
  const delayActionKey = context.delay_action_key ?? 'core:delay';

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
    hasDelayFreePathToStep(
      trigger,
      step,
      steps as Record<string, Step>,
      delayActionKey,
    ),
  );

  return allTriggersHaveDelayFreePath;
}
