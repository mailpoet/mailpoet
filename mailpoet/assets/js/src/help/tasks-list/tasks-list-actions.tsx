import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function TaskButton({ id, type }: { id: number, type: string }): JSX.Element {
  const isCancelButton = type === 'cancel';

  return (
    <>
      <Button
        variant="secondary"
        size="small"
        isDestructive={isCancelButton}
      >
        {isCancelButton ? __('Cancel task', 'mailpoet'): __('Reschedule task', 'mailpoet')}
      </Button>
    </>
  );
}

export function CancelTaskButton({ id }: { id: number }): JSX.Element {
  return <TaskButton id={id} type="cancel" />;
}

export function RescheduleTaskButton({ id }: { id: number }): JSX.Element {
  return <TaskButton id={id} type="reschedule" />;
}
