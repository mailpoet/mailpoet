import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

type TasksListDataRowProps = {
  id: number;
  type: string;
  priority: number;
  updatedAt: string;
  scheduledAt?: string;
  cancelledAt?: string;
  status?: string;
  newsletter: {
    newsletterId?: number;
    queueId?: number;
    previewUrl?: string;
    subject?: string;
  };
  subscriberEmail?: string;
};

type Props = {
  task: TasksListDataRowProps;
  type: 'cancel' | 'reschedule';
};

function TaskButton({ task, type }: Props): JSX.Element {
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

type ButtonProps = {
  task: TasksListDataRowProps;
};

export function CancelTaskButton({ task }: ButtonProps): JSX.Element {
  return <TaskButton task={task} type="cancel" />;
}

export function RescheduleTaskButton({ task }: ButtonProps): JSX.Element {
  return <TaskButton task={task} type="reschedule" />;
}
