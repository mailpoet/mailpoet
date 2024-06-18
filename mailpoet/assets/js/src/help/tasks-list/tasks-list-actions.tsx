import {
  Button,
  __experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
import { useState } from 'react';
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
  const [showConfirmDialog, setShowConfirmDialog] = useState(false);
  const isCancelButton = type === 'cancel';

  return (
    <>
      <ConfirmDialog
        isOpen={showConfirmDialog}
        title={
          isCancelButton
            ? __('Cancel task', 'mailpoet')
            : __('Reschedule task', 'mailpoet')
        }
        cancelButtonText={__('Not now', 'mailpoet')}
        confirmButtonText={
          isCancelButton
            ? __('Yes, cancel task', 'mailpoet')
            : __('Yes, reschedule task', 'mailpoet')
        }
        onConfirm={() => {}}
        onCancel={() => setShowConfirmDialog(false)}
        __experimentalHideHeader={false}
      >
        <div />
      </ConfirmDialog>

      <Button
        variant="secondary"
        size="small"
        isDestructive={isCancelButton}
        onClick={() => setShowConfirmDialog(true)}
      >
        {isCancelButton
          ? __('Cancel task', 'mailpoet')
          : __('Reschedule task', 'mailpoet')}
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
