import { __ } from '@wordpress/i18n';
import { WorkflowProps } from '../workflow';

interface EditProps extends WorkflowProps {
  label?: string;
}

export function Edit({ workflow, label }: EditProps): JSX.Element {
  return (
    <a href={`admin.php?page=mailpoet-automation-editor&id=${workflow.id}`}>
      {label ?? __('Edit', 'mailpoet')}
    </a>
  );
}
