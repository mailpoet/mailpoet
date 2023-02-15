import { ToggleControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../../editor/store';

export function RunAutomationOnce(): JSX.Element {
  const { automationData } = useSelect(
    (select) => ({
      automationData: select(storeName).getAutomationData(),
    }),
    [],
  );
  const checked =
    (automationData.meta?.run_automation_once as boolean) || false;
  return (
    <ToggleControl
      label={__('Run this automation only once per subscriber.', 'mailpoet')}
      checked={checked}
      onChange={(value) => {
        dispatch(storeName).updateAutomationMeta('run_automation_once', value);
      }}
    />
  );
}
