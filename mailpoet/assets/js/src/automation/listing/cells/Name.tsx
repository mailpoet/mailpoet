import { Edit } from './Edit';
import { WorkflowProps } from '../workflow';

export function Name({ workflow }: WorkflowProps): JSX.Element {
  return <Edit workflow={workflow} label={workflow.name} />;
}
