import {
  PanelBody,
  TextControl,
  SelectControl,
  Flex,
  FlexItem,
} from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { PlainBodyTitle } from '../../../../editor/components/panel';
import { storeName } from '../../../../editor/store';
import { DelayTypeOptions } from './types/delayTypes';

export function Edit(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );

  return (
    <PanelBody opened>
      <PlainBodyTitle title="Wait for" />
      <Flex align="top">
        <FlexItem style={{ flex: '1 1 0' }}>
          <TextControl
            label=""
            type="number"
            placeholder="Number"
            value={(selectedStep.args.delay as string) ?? ''}
            onChange={(rawValue) => {
              const value: number =
                rawValue.length === 0 || parseInt(rawValue, 10) < 1
                  ? 1
                  : parseInt(rawValue, 10);
              dispatch(storeName).updateStepArgs(
                selectedStep.id,
                'delay',
                value,
              );
            }}
          />
        </FlexItem>
        <FlexItem style={{ flex: '1 1 0' }}>
          <SelectControl
            label=""
            value={(selectedStep.args.delay_type as string) ?? 'HOURS'}
            options={DelayTypeOptions}
            onChange={(value) =>
              dispatch(storeName).updateStepArgs(
                selectedStep.id,
                'delay_type',
                value,
              )
            }
          />
        </FlexItem>
      </Flex>
    </PanelBody>
  );
}
