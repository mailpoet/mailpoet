import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../../../../editor/store';

export function ReplyToPanel(): JSX.Element {
  const { selectedStep, errors } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
      errors: select(storeName).getStepError(
        select(storeName).getSelectedStep().id,
      ),
    }),
    [],
  );

  const replyToName = selectedStep.args.reply_to_name as string | undefined;
  const replyToAddress = selectedStep.args.reply_to_address as
    | string
    | undefined;

  const enabled =
    typeof replyToName !== 'undefined' || typeof replyToAddress !== 'undefined';

  const errorFields = errors?.fields ?? {};
  const replyToNameError = errorFields?.reply_to_name ?? '';
  const replyToAddressError = errorFields?.reply_to_address ?? '';
  return (
    <PanelBody title={__('Reply to', 'mailpoet')} initialOpen={false}>
      <ToggleControl
        label={__(
          'Use different email address for getting replies to the email',
          'mailpoet',
        )}
        checked={enabled}
        onChange={(value) => {
          dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'reply_to_name',
            value ? '' : undefined,
          );
          dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'reply_to_address',
            value ? '' : undefined,
          );
        }}
      />

      {enabled && (
        <>
          <TextControl
            className={
              replyToNameError ? 'mailpoet-automation-field__error' : ''
            }
            help={replyToNameError}
            label={__('“Reply to” name', 'mailpoet')}
            placeholder={__('John Doe', 'mailpoet')}
            value={replyToName ?? ''}
            onChange={(value) =>
              dispatch(storeName).updateStepArgs(
                selectedStep.id,
                'reply_to_name',
                value,
              )
            }
          />

          <TextControl
            className={
              replyToAddressError ? 'mailpoet-automation-field__error' : ''
            }
            help={replyToAddressError}
            type="email"
            label={__('“Reply to” email address', 'mailpoet')}
            placeholder={__('you@domain.com', 'mailpoet')}
            value={replyToAddress ?? ''}
            onChange={(value) =>
              dispatch(storeName).updateStepArgs(
                selectedStep.id,
                'reply_to_address',
                value,
              )
            }
          />
        </>
      )}
    </PanelBody>
  );
}
