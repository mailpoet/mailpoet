import PropTypes from 'prop-types';
import { Edit } from './Edit';
import { WorkflowProps, WorkflowPropsShape } from '../workflow';

export function Name({ workflow }: WorkflowProps): JSX.Element {
  return <Edit workflow={workflow} label={workflow.name} />;
}

Name.propTypes = {
  workflow: PropTypes.shape(WorkflowPropsShape).isRequired,
};
