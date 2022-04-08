import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import KeyValueTable from 'common/key_value_table.jsx';
import TasksList from './tasks_list/tasks_list.jsx';
import TasksListDataRow from './tasks_list/tasks_list_data_row.jsx';

function QueueStatus(props) {
  const status = props.status_data;
  return (
    <>
      <h4>{MailPoet.I18n.t('systemStatusQueueTitle')}</h4>
      <KeyValueTable
        max_width="400px"
        rows={[
          {
            key: MailPoet.I18n.t('status'),
            value:
              status.status === 'paused'
                ? MailPoet.I18n.t('paused')
                : MailPoet.I18n.t('running'),
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
            key: MailPoet.I18n.t('retryAttempt'),
            value: status.retry_attempt || MailPoet.I18n.t('none'),
          },
          {
            key: MailPoet.I18n.t('retryAt'),
            value: status.retry_at
              ? MailPoet.Date.full(status.retry_at * 1000)
              : MailPoet.I18n.t('none'),
          },
          {
            key: MailPoet.I18n.t('error'),
            value: status.error
              ? status.error.error_message
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
            key: MailPoet.I18n.t('totalScheduledTasks'),
            value: status.tasksStatusCounts.scheduled,
          },
        ]}
      />
      <h5>{MailPoet.I18n.t('scheduledTasks')}</h5>
      <TasksList
        show_scheduled_at
        tasks={status.latestTasks.filter((task) => task.status === 'scheduled')}
      />

      <h5>{MailPoet.I18n.t('runningTasks')}</h5>
      <TasksList
        tasks={status.latestTasks.filter((task) => task.status === null)}
      />

      <h5>{MailPoet.I18n.t('completedTasks')}</h5>
      <TasksList
        tasks={status.latestTasks.filter((task) => task.status === 'completed')}
      />
    </>
  );
}

QueueStatus.propTypes = {
  status_data: PropTypes.shape({
    status: PropTypes.string,
    started: PropTypes.number,
    sent: PropTypes.number,
    retry_attempt: PropTypes.number,
    retry_at: PropTypes.number,
    error: PropTypes.shape({
      operation: PropTypes.string,
      error_message: PropTypes.string,
    }),
    tasksStatusCounts: PropTypes.shape({
      completed: PropTypes.number.isRequired,
      running: PropTypes.number.isRequired,
      paused: PropTypes.number.isRequired,
      scheduled: PropTypes.number.isRequired,
    }).isRequired,
    latestTasks: PropTypes.arrayOf(TasksListDataRow.propTypes.task).isRequired,
  }).isRequired,
};

export default QueueStatus;
