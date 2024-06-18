import {
  Button,
  __experimentalConfirmDialog as ConfirmDialog,
  Notice,
} from '@wordpress/components';
import { MailPoet } from 'mailpoet';
import { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';

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
  const [errorMessage, setErrorMessage] = useState(null);
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
        onConfirm={async () => {
          await MailPoet.Ajax.post({
            api_version: window.mailpoet_api_version,
            endpoint: 'help',
            action: isCancelButton ? 'cancelTask' : 'rescheduleTask',
            data: {
              id: task.id,
            },
          })
            .done(() => {
              setErrorMessage(null);
              setShowConfirmDialog(false);
              window.location.reload();
            })
            .catch((e) => {
              setErrorMessage(e.errors.map((error) => error.message).join(' '));
            });
        }}
        onCancel={() => setShowConfirmDialog(false)}
        __experimentalHideHeader={false}
      >
        {errorMessage && (
          <>
            <Notice status="error" isDismissible={false}>
              {errorMessage}
            </Notice>
            <br />
          </>
        )}
        {isCancelButton &&
          sprintf(
            // translators: %1$s is a number, %2$s is the email subject (when empty, "(no subject)" is used)
            __(
              'Are you sure you want to cancel the task with ID %1$s for the email "%2$s"? Once cancelled, the email will not be sent.',
              'mailpoet',
            ),
            task.id,
            // translators: used when the email subject is empty
            task.newsletter.subject || __('(no subject)', 'mailpoet'),
          )}
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
