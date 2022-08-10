import { ComponentProps } from 'react';
import { PanelBody, TextareaControl, TextControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { DesignEmailButton } from './design_email_button';
import { Thumbnail } from './thumbnail';
import { PlainBodyTitle } from '../../../../../editor/components/panel';
import { store } from '../../../../../editor/store';
import {StepName} from "../../../../../editor/components/panel/step-name";

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
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(store).getSelectedStep(),
    }),
    [],
  );

  return (
    <PanelBody opened>
      <StepName step={selectedStep} />
      <TextControl
        label="“From” name"
        placeholder="John Doe"
        value={(selectedStep.args.sender_name as string) ?? ''}
        onChange={(value) =>
          dispatch(store).updateStepArgs(selectedStep.id, 'sender_name', value)
        }
      />
      <TextControl

        type="email"
        label="“From” email address"
        placeholder="you@domain.com"
        value={(selectedStep.args.sender_address as string) ?? ''}
        onChange={(value) =>
          dispatch(store).updateStepArgs(
            selectedStep.id,
            'sender_address',
            value,
          )
        }
      />
      <SingleLineTextareaControl
        label="Subject"
        placeholder="Type in subject…"
        value={(selectedStep.args.subject as string) ?? ''}
        onChange={(value) =>
          dispatch(store).updateStepArgs(selectedStep.id, 'subject', value)
        }
      />
      <SingleLineTextareaControl
        label="Preheader"
        placeholder="Type in preheader…"
        value={(selectedStep.args.preheader as string) ?? ''}
        onChange={(value) =>
          dispatch(store).updateStepArgs(selectedStep.id, 'preheader', value)
        }
      />

      <div className="mailpoet-automation-email-content-separator" />
      <PlainBodyTitle title="Email content" />
      {selectedStep.args.email_id ? (
        <Thumbnail emailId={selectedStep.args.email_id as number} />
      ) : (
        <DesignEmailButton />
      )}
    </PanelBody>
  );
}
