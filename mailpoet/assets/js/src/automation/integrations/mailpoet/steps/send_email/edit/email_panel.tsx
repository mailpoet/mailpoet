import { ComponentProps } from 'react';
import { PanelBody, TextareaControl, TextControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { ShortcodeHelpText } from './shortcode_help_text';
import { PlainBodyTitle } from '../../../../../editor/components/panel';
import { storeName } from '../../../../../editor/store';
import { StepName } from '../../../../../editor/components/panel/step-name';
import { EditNewsletter } from './edit_newsletter';

function SingleLineTextareaControl(
  props: ComponentProps<typeof TextareaControl>,
): JSX.Element {
  return (
    <TextareaControl
      {...props}
      onChange={(value) =>
        // replace a newline or a group of multiple newlines by a space (text pasting)
        props.onChange(value.replaceAll(/(\r?\n)+/g, ' '))
      }
      onKeyDown={(event) => {
        // disable inserting newlines via "Enter" key
        if (event.key === 'Enter') {
          event.preventDefault();
        }
        if (props.onKeyDown) {
          props.onKeyDown(event);
        }
      }}
    />
  );
}

export function EmailPanel(): JSX.Element {
  const { selectedStep, selectedStepType, errors } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
      selectedStepType: select(storeName).getSelectedStepType(),
      errors: select(storeName).getStepError(
        select(storeName).getSelectedStep().id,
      ),
    }),
    [],
  );

  const errorFields = errors?.fields ?? {};
  const senderNameErrorMessage = errorFields?.sender_name ?? '';
  const senderAddressErrorMessage = errorFields?.sender_address ?? '';
  const subjectErrorMessage = errorFields?.subject ?? '';
  return (
    <PanelBody opened>
      <StepName
        currentName={(selectedStep.args.name as string) ?? ''}
        defaultName={selectedStepType.title}
        update={(value) => {
          dispatch(storeName).updateStepArgs(selectedStep.id, 'name', value);
        }}
      />
      <TextControl
        className={
          senderNameErrorMessage ? 'mailpoet-automation-field__error' : ''
        }
        help={senderNameErrorMessage}
        label="“From” name"
        placeholder="John Doe"
        value={(selectedStep.args.sender_name as string) ?? ''}
        onChange={(value) =>
          dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'sender_name',
            value,
          )
        }
      />
      <TextControl
        className={
          senderAddressErrorMessage ? 'mailpoet-automation-field__error' : ''
        }
        help={senderAddressErrorMessage}
        type="email"
        label="“From” email address"
        placeholder="you@domain.com"
        value={(selectedStep.args.sender_address as string) ?? ''}
        onChange={(value) =>
          dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'sender_address',
            value,
          )
        }
      />
      <SingleLineTextareaControl
        className={
          subjectErrorMessage ? 'mailpoet-automation-field__error' : ''
        }
        label="Subject"
        placeholder="Type in subject…"
        value={(selectedStep.args.subject as string) ?? ''}
        onChange={(value) =>
          dispatch(storeName).updateStepArgs(selectedStep.id, 'subject', value)
        }
        help={
          <>
            {`${subjectErrorMessage} `}
            <ShortcodeHelpText />
          </>
        }
      />
      <SingleLineTextareaControl
        label="Preheader"
        placeholder="Type in preheader…"
        value={(selectedStep.args.preheader as string) ?? ''}
        onChange={(value) =>
          dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'preheader',
            value,
          )
        }
        help={<ShortcodeHelpText />}
      />

      <div className="mailpoet-automation-email-content-separator" />
      <PlainBodyTitle title="Email" />
      <EditNewsletter />
    </PanelBody>
  );
}
