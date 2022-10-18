import { Workflow } from '../../workflow';
import { EditWorkflow } from '../actions/edit-workflow';

type Props = {
  workflow: Workflow;
  label?: string;
};

export function Edit({ workflow, label }: Props): JSX.Element {
  return <EditWorkflow workflow={workflow} label={label} />;
}
