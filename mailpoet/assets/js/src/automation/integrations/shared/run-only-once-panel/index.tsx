import { PanelBody, ToggleControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../../editor/store';
import { PlainBodyTitle } from '../../../editor/components';

export function RunOnlyOncePanel(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );
  const checked: boolean = selectedStep.args?.run_multiple_times as boolean;
  return (
    <PanelBody opened>
      <PlainBodyTitle title={__('Automation setting', 'mailpoet')} />

      <ToggleControl
        label={__(
          'A subscriber can enter this automation multiple times.',
          'mailpoet',
        )}
        checked={checked}
        onChange={(value) => {
          dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'run_multiple_times',
            value,
          );
        }}
      />
    </PanelBody>
  );
}
