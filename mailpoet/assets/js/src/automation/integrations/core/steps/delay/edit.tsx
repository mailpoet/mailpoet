import {
  PanelBody,
  TextControl,
  SelectControl,
  Flex,
  FlexItem,
} from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { PlainBodyTitle } from '../../../../editor/components/panel';
import { storeName } from '../../../../editor/store';
import { DelayTypeOptions } from './types/delayTypes';

export function Edit(): JSX.Element {
  const { selectedStep, errors } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
      errors: select(storeName).getStepError(
        select(storeName).getSelectedStep().id,
      ),
    }),
    [],
  );

  const errorFields = errors?.fields ?? {};
  const delayErrorMessage = errorFields?.delay ?? '';
  const delayTypeErrorMessage = errorFields?.delay_type ?? '';
  const delayValueInputId = `delay-number-${selectedStep.id}`;
  return (
    <PanelBody opened>
      <label htmlFor={delayValueInputId}>
        <PlainBodyTitle title={__('Wait for', 'mailpoet')} />
      </label>
      <Flex align="top">
        <FlexItem
          style={{ flex: '1 1 0' }}
          className={
            delayErrorMessage ? 'mailpoet-automation-field__error' : ''
          }
        >
          <TextControl
            id={delayValueInputId}
            help={delayErrorMessage}
            type="number"
            placeholder={__('Number', 'mailpoet')}
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
        <FlexItem
          style={{ flex: '1 1 0' }}
          className={
            delayTypeErrorMessage ? 'mailpoet-automation-field__error' : ''
          }
        >
          <SelectControl
            label=""
            help={delayTypeErrorMessage}
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
