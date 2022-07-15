import { PanelBody, TextareaControl, TextControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { plus, edit, Icon } from '@wordpress/icons';
import { Thumbnail } from './thumbnail';
import { Button } from '../../components/button';
import {
  PlainBodyTitle,
  TitleActionButton,
} from '../../../../editor/components/panel';
import { store } from '../../../../editor/store';

export function Edit(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(store).getSelectedStep(),
    }),
    [],
  );

  return (
    <PanelBody opened>
      <PlainBodyTitle title="Email">
        <TitleActionButton>
          <Icon icon={edit} size={16} />
        </TitleActionButton>
      </PlainBodyTitle>
      <TextControl
        label="“From” name"
        placeholder="John Doe"
        value={(selectedStep.args.from_name as string) ?? ''}
        onChange={(value) =>
          dispatch(store).updateStepArgs(selectedStep.id, 'from_name', value)
        }
      />
      <TextControl
        type="email"
        label="“From” email address"
        placeholder="you@domain.com"
        value={(selectedStep.args.email as string) ?? ''}
        onChange={(value) =>
          dispatch(store).updateStepArgs(selectedStep.id, 'email', value)
        }
      />
      <TextareaControl
        label="Subject"
        placeholder="Type in subject…"
        value={(selectedStep.args.subject as string) ?? ''}
        onChange={(value) =>
          dispatch(store).updateStepArgs(selectedStep.id, 'subject', value)
        }
      />
      <TextareaControl
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
        <Button variant="sidebar-primary" centered icon={plus}>
          Design email
        </Button>
      )}
    </PanelBody>
  );
}
