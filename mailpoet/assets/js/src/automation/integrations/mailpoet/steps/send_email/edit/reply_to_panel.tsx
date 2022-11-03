import { useRef, useState } from 'react';
import { TextControl, ToggleControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../../../../editor/store';
import { PanelBody } from '../../../../../editor/components/panel/panel-body';

type ReplyToArgs = {
  reply_to_name?: string;
  reply_to_address?: string;
};

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

  const { updateStepArgs } = useDispatch(storeName);

  const args = selectedStep.args as ReplyToArgs;
  const hasValue = !!args.reply_to_name || !!args.reply_to_address;
  const [expanded, setExpanded] = useState(hasValue);
  const prevValue = useRef<{ name?: string; address?: string }>();

  const errorFields = errors?.fields ?? {};
  const replyToNameError = errorFields?.reply_to_name ?? '';
  const replyToAddressError = errorFields?.reply_to_address ?? '';
  return (
    <PanelBody
      title={__('Reply to', 'mailpoet')}
      initialOpen={false}
      hasErrors={!!replyToNameError || !!replyToAddressError}
    >
      <ToggleControl
        label={__(
          'Use different email address for getting replies to the email',
          'mailpoet',
        )}
        checked={expanded}
        onChange={(value) => {
          setExpanded(value);
          const stepId = selectedStep.id;
          if (value) {
            updateStepArgs(stepId, 'reply_to_name', prevValue.current?.name);
            updateStepArgs(
              stepId,
              'reply_to_address',
              prevValue.current?.address,
            );
          } else {
            prevValue.current = {
              name: args.reply_to_name,
              address: args.reply_to_address,
            };
            updateStepArgs(stepId, 'reply_to_name', undefined);
            updateStepArgs(stepId, 'reply_to_address', undefined);
          }
        }}
      />

      {expanded && (
        <>
          <TextControl
            className={
              replyToNameError ? 'mailpoet-automation-field__error' : ''
            }
            help={replyToNameError}
            label={__('"Reply to" name', 'mailpoet')}
            placeholder={
              // translators: A placeholder for a person's name
              __('John Doe', 'mailpoet')
            }
            value={args.reply_to_name ?? ''}
            onChange={(value) =>
              updateStepArgs(
                selectedStep.id,
                'reply_to_name',
                value || undefined,
              )
            }
          />

          <TextControl
            className={
              replyToAddressError ? 'mailpoet-automation-field__error' : ''
            }
            help={replyToAddressError}
            type="email"
            label={__('"Reply to" email address', 'mailpoet')}
            placeholder={
              // translators: A placeholder for an email
              __('you@domain.com', 'mailpoet')
            }
            value={args.reply_to_address ?? ''}
            onChange={(value) =>
              updateStepArgs(
                selectedStep.id,
                'reply_to_address',
                value || undefined,
              )
            }
          />
        </>
      )}
    </PanelBody>
  );
}
