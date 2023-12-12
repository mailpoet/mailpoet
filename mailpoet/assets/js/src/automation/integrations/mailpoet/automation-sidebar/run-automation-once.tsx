import { ToggleControl } from '@wordpress/components';
import { dispatch, select, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../../editor/store';

export function showRunOnlyOnce(): boolean {
  const automation = select(storeName).getAutomationData();
  const triggers = Object.values(automation.steps).filter(
    (step) => step.type === 'trigger',
  );
  if (triggers.length === 0) {
    return true;
  }

  const subscriberTriggers = triggers.filter((trigger) =>
    select(storeName)
      .getStepSubjectKeys(trigger.key)
      .includes('mailpoet:subscriber'),
  );
  return subscriberTriggers.length > 0;
}

export function RunAutomationOnce(): JSX.Element {
  const { automationData } = useSelect(
    (s) => ({
      automationData: s(storeName).getAutomationData(),
    }),
    [],
  );

  const checked =
    (automationData.meta?.['mailpoet:run-once-per-subscriber'] as boolean) ||
    false;
  return (
    <ToggleControl
      className="mailpoet-automation-run-only-once"
      label={__('Run this automation only once per subscriber.', 'mailpoet')}
      checked={checked}
      onChange={(value) => {
        void dispatch(storeName).updateAutomationMeta(
          'mailpoet:run-once-per-subscriber',
          value,
        );
      }}
    />
  );
}
