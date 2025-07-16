import { __ } from '@wordpress/i18n';
import { dispatch, useSelect } from '@wordpress/data';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { PlainBodyTitle } from '../../../../../editor/components';
import { storeName } from '../../../../../editor/store';

export function Edit(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );

  const noteType = (selectedStep.args?.note_type as string) ?? 'all';
  const noteContains = (selectedStep.args?.note_contains as string) ?? '';

  return (
    <PanelBody opened>
      <PlainBodyTitle title={__('Trigger settings', 'mailpoet')} />

      <SelectControl
        label={__('Note type', 'mailpoet')}
        value={noteType}
        options={[
          { label: __('All', 'mailpoet'), value: 'all' },
          { label: __('Note to customer', 'mailpoet'), value: 'customer' },
          { label: __('Private note', 'mailpoet'), value: 'private' },
        ]}
        onChange={(value) => {
          void dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'note_type',
            value,
          );
        }}
      />

      <TextControl
        label={__('Note contains text', 'mailpoet')}
        help={__(
          'Only trigger this workflow if the order note contains the certain text. This field is optional.',
          'mailpoet',
        )}
        value={noteContains}
        onChange={(value) => {
          void dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'note_contains',
            value,
          );
        }}
      />
    </PanelBody>
  );
}
