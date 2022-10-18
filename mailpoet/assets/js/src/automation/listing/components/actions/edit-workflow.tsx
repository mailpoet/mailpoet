import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { Workflow } from '../../workflow';
import { MailPoet } from '../../../../mailpoet';

type Props = {
  workflow: Workflow;
  label?: string;
};

export function EditWorkflow({ workflow, label }: Props): JSX.Element {
  return (
    <Button
      variant="link"
      href={addQueryArgs(MailPoet.urls.automationEditor, { id: workflow.id })}
    >
      {label ?? __('Edit', 'mailpoet')}
    </Button>
  );
}
