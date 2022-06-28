import { Edit } from './Edit';
import { Workflow } from '../workflow';

type Props = {
  workflow: Workflow;
};

export function Name({ workflow }: Props): JSX.Element {
  return <Edit workflow={workflow} label={workflow.name} />;
}
