import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { storeName } from '../../../../../editor/store';

export function ReplyToPanel(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );

  const replyToName = selectedStep.args.reply_to_name as string | undefined;
  const replyToAddress = selectedStep.args.reply_to_address as
    | string
    | undefined;

  const enabled =
    typeof replyToName !== 'undefined' || typeof replyToAddress !== 'undefined';

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
