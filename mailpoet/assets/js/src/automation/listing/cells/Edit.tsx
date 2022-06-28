import { __ } from '@wordpress/i18n';
import { Workflow } from '../workflow';

type Props = {
  workflow: Workflow;
  label?: string;
};

export function Edit({ workflow, label }: Props): JSX.Element {
  return (
    <a href={`admin.php?page=mailpoet-automation-editor&id=${workflow.id}`}>
      {label ?? __('Edit', 'mailpoet')}
    </a>
  );
}
