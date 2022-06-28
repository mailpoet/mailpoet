import { Workflow } from '../workflow';

type Props = {
  workflow: Workflow;
};

export function Subscribers({ workflow }: Props): JSX.Element {
  return <p>ToDo {workflow.id}</p>;
}
