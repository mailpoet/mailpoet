import {
  PanelBody,
  TextControl,
  SelectControl,
  Flex,
  FlexItem,
  BaseControl,
} from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { PlainBodyTitle } from '../../../../editor/components/panel';
import { storeName } from '../../../../editor/store';
import { DelayTypeOptions } from './types/delay-types';

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
  return (
    <PanelBody opened>
      <PlainBodyTitle title={__('Settings', 'mailpoet')} />
      <BaseControl
        label={
          // translators: A label for a wait delay time selection form field - time unit follows
          __('Wait for', 'mailpoet')
        }
      >
        <Flex align="top">
          <FlexItem
            style={{ flex: '1 1 0' }}
            className={
              delayErrorMessage ? 'mailpoet-automation-field__error' : ''
            }
          >
            <TextControl
              name="delay-number"
              help={delayErrorMessage}
              type="number"
              placeholder={__('Number', 'mailpoet')}
              value={(selectedStep.args.delay as string) ?? ''}
              onChange={(rawValue) => {
                const value: number =
                  rawValue.length === 0 || parseInt(rawValue, 10) < 1
                    ? 1
                    : parseInt(rawValue, 10);
                void dispatch(storeName).updateStepArgs(
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
              name="delay-value"
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
      </BaseControl>
    </PanelBody>
  );
}
