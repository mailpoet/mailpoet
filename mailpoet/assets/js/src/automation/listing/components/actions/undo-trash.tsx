import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../store/constants';
import { Workflow, WorkflowStatus } from '../../workflow';

type Props = {
  workflow: Workflow;
  previousStatus: WorkflowStatus;
};

export function UndoTrashButton({
  workflow,
  previousStatus,
}: Props): JSX.Element {
  const { restoreWorkflow } = useDispatch(storeName);

  return (
    <Button
      variant="link"
      onClick={() => restoreWorkflow(workflow, previousStatus)}
    >
      {__('Undo', 'mailpoet')}
    </Button>
  );
}
