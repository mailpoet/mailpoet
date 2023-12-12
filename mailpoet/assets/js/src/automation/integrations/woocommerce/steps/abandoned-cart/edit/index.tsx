import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl } from '@wordpress/components';
import { PlainBodyTitle } from '../../../../../editor/components';
import { storeName } from '../../../../../editor/store';

export function Edit(): JSX.Element {
  const { selectedStep } = useSelect((select) => ({
    selectedStep: select(storeName).getSelectedStep(),
  }));

  return (
    <PanelBody opened>
      <PlainBodyTitle title={__('Settings', 'mailpoet')} />
      <SelectControl
        onChange={(id: string) => {
          void dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'wait',
            parseInt(id, 10),
          );
        }}
        defaultValue={(selectedStep.args?.wait as string) ?? '30'}
      >
        <option value="30">{__('30 minutes', 'mailpoet')}</option>
        <option value="60">{__('1 hour', 'mailpoet')}</option>
        <option value="240">{__('4 hours', 'mailpoet')}</option>
        <option value="1440">{__('1 day', 'mailpoet')}</option>
        <option value="4320">{__('3 days', 'mailpoet')}</option>
      </SelectControl>
    </PanelBody>
  );
}
