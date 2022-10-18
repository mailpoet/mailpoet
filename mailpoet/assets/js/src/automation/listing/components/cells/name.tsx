import { EditWorkflow } from '../actions';
import { Workflow } from '../../workflow';

type Props = {
  workflow: Workflow;
};

export function Name({ workflow }: Props): JSX.Element {
  return <EditWorkflow workflow={workflow} label={workflow.name} />;
}
