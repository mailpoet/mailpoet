import { PanelBody } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../../../../editor/store';
import { segments } from './segment';
import {
  PlainBodyTitle,
  FormTokenField,
} from '../../../../../editor/components';

export function ListPanel(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );

  const rawSelected = selectedStep.args?.segment_ids
    ? (selectedStep.args.segment_ids as number[])
    : [];

  const validSegments = segments.filter(
    (segment) => segment.type === 'default',
  );
  const selected = validSegments.filter((segment): boolean =>
    rawSelected.includes(segment.id as number),
  );
  return (
    <PanelBody opened>
      <PlainBodyTitle title={__('Trigger settings', 'mailpoet')} />

      <FormTokenField
        label={__(
          'When someone subscribes to the following lists:',
          'mailpoet',
        )}
        placeholder={__('Any list', 'mailpoet')}
        value={selected}
        suggestions={validSegments}
        onChange={(values) => {
          dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'segment_ids',
            values.map((item) => item.id),
          );
        }}
      />
    </PanelBody>
  );
}
