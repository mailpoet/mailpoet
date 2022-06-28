import { WorkflowProps } from '../workflow';

export function Subscribers({ workflow }: WorkflowProps): JSX.Element {
  return <p>ToDo {workflow.id}</p>;
}
