import PropTypes from 'prop-types';
import { WorkflowProps, WorkflowPropsShape } from '../workflow';

export function Subscribers({ workflow }: WorkflowProps): JSX.Element {
  return <p>ToDo {workflow.id}</p>;
}
Subscribers.propTypes = {
  workflow: PropTypes.shape(WorkflowPropsShape),
};
