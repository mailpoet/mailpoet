import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { WorkflowProps, WorkflowPropsShape } from '../workflow';

interface EditProps extends WorkflowProps {
  label?: string;
}
export function Edit({ workflow, label = null }: EditProps): JSX.Element {
  return (
    <a href={`admin.php?page=mailpoet-automation-editor&id=${workflow.id}`}>
      {label ?? __('Edit', 'mailpoet')}
    </a>
  );
}

Edit.propTypes = {
  workflow: PropTypes.shape(WorkflowPropsShape).isRequired,
  label: PropTypes.string,
};
