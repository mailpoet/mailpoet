import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
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
    <PanelBody title="Reply to" initialOpen={false}>
      <ToggleControl
        label="Use different email address for getting replies to the email"
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
            label="“Reply to” name"
            placeholder="John Doe"
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
            label="“Reply to” email address"
            placeholder="you@domain.com"
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
