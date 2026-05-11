import { Registry } from '../../../../editor/store/types';
import { Automation } from '../../../../editor/components/automation/types';
import { Item } from '../../../../editor/components/inserter/item';

export const sendLatestNewsletterStepKey = 'mailpoet:send-latest-newsletter';

const subscriberSubjectKey = 'mailpoet:subscriber';
const segmentSubjectKey = 'mailpoet:segment';

const segmentProducingTriggerKeys = new Set([
  'mailpoet:someone-subscribes',
  'mailpoet:wp-user-registered',
]);

export function stepProvidesTriggerList(
  stepKey: string,
  registry?: Registry,
): boolean {
  const subjectKeys = registry?.steps?.[stepKey]?.subject_keys;

  if (Array.isArray(subjectKeys)) {
    return (
      subjectKeys.includes(subscriberSubjectKey) &&
      subjectKeys.includes(segmentSubjectKey)
    );
  }

  return segmentProducingTriggerKeys.has(stepKey);
}

export function automationHasTriggerList(
  automation: Automation,
  registry?: Registry,
): boolean {
  return Object.values(automation.steps ?? {}).some(
    (step) =>
      step.type === 'trigger' && stepProvidesTriggerList(step.key, registry),
  );
}

export function disableSendLatestNewsletterWhenMissingTriggerList(
  items: Item[],
  automation: Automation,
  registry: Registry | undefined,
  disabledReason: string,
): Item[] {
  if (automationHasTriggerList(automation, registry)) {
    return items;
  }

  return items.map((item) =>
    item.key === sendLatestNewsletterStepKey
      ? { ...item, isDisabled: true, disabledReason }
      : item,
  );
}
