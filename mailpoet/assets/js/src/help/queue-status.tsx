import { MailPoet } from 'mailpoet';
import { KeyValueTable } from 'common/key-value-table';
import { getQueueStatusLabelKey } from './status-labels';
import { TasksList } from './tasks-list/tasks-list';
import { Props as TasksListDataRowProps } from './tasks-list/tasks-list-data-row';

type Props = {
  statusData: {
    status?: string | null;
    started?: number | null;
    sent?: number;
    retryAttempt?: number | null;
    retryAt?: number | null;
    error?: {
      operation?: string | null;
      errorMessage?: string | null;
    } | null;
    tasksStatusCounts: {
      completed: number;
      running: number;
      paused: number;
      scheduled: number;
      cancelled: number;
    };
    latestTasks: TasksListDataRowProps['task'][];
  };
};

function QueueStatus({ statusData }: Props): JSX.Element {
  const status = statusData;
  return (
    <>
      <h4>{MailPoet.I18n.t('systemStatusQueueTitle')}</h4>
      <KeyValueTable
        max_width="400px"
        rows={[
          {
            key: MailPoet.I18n.t('status'),
            value: MailPoet.I18n.t(getQueueStatusLabelKey(status.status)),
          },
          {
            key: MailPoet.I18n.t('startedAt'),
            value: status.started
              ? MailPoet.Date.full(status.started * 1000)
              : MailPoet.I18n.t('unknown'),
          },
          {
            key: MailPoet.I18n.t('sentEmails'),
            value: status.sent || 0,
          },
          {
            key: MailPoet.I18n.t('retryAttempts'),
            value: status.retryAttempt || 0,
          },
          {
            key: MailPoet.I18n.t('retryAt'),
            value: status.retryAt
              ? MailPoet.Date.full(status.retryAt * 1000)
              : MailPoet.I18n.t('none'),
          },
          {
            key: MailPoet.I18n.t('error'),
            value: status.error
              ? status.error.errorMessage
              : MailPoet.I18n.t('none'),
          },
          {
            key: MailPoet.I18n.t('totalCompletedTasks'),
            value: status.tasksStatusCounts.completed,
          },
          {
            key: MailPoet.I18n.t('totalRunningTasks'),
            value: status.tasksStatusCounts.running,
          },
          {
            key: MailPoet.I18n.t('totalPausedTasks'),
            value: status.tasksStatusCounts.paused,
          },
          {
            key: MailPoet.I18n.t('totalCancelledTasks'),
            value: status.tasksStatusCounts.cancelled,
          },
          {
            key: MailPoet.I18n.t('totalScheduledTasks'),
            value: status.tasksStatusCounts.scheduled,
          },
        ]}
      />
      <h5>{MailPoet.I18n.t('scheduledTasks')}</h5>
      <TasksList
        type="scheduled"
        tasks={status.latestTasks.filter((task) => task.status === 'scheduled')}
      />

      <h5>{MailPoet.I18n.t('cancelledTasks')}</h5>
      <TasksList
        type="cancelled"
        tasks={status.latestTasks.filter((task) => task.status === 'cancelled')}
      />

      <h5>{MailPoet.I18n.t('runningTasks')}</h5>
      <TasksList
        type="running"
        tasks={status.latestTasks.filter((task) => task.status === null)}
      />

      <h5>{MailPoet.I18n.t('pausedTasks')}</h5>
      <TasksList
        type="paused"
        tasks={status.latestTasks.filter((task) => task.status === 'paused')}
      />

      <h5>{MailPoet.I18n.t('completedTasks')}</h5>
      <TasksList
        type="completed"
        tasks={status.latestTasks.filter((task) => task.status === 'completed')}
      />
    </>
  );
}

export { QueueStatus };
