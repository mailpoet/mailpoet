import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { TasksListDataRow } from './tasks-list-data-row.jsx';
import { TasksListLabelsRow } from './tasks-list-labels-row.jsx';

function TasksList({ tasks, show_scheduled_at: showScheduledAt = false }) {
  const colsCount = showScheduledAt ? 6 : 5;

  return (
    <table className="widefat fixed striped">
      <thead>
        <TasksListLabelsRow show_scheduled_at={showScheduledAt} />
      </thead>
      <tbody>
        {tasks.length ? (
          tasks.map((task) => (
            <TasksListDataRow
              key={task.id}
              task={task}
              show_scheduled_at={showScheduledAt}
            />
          ))
        ) : (
          <tr className="mailpoet-listing-no-items">
            <td colSpan={colsCount}>{MailPoet.I18n.t('nothingToShow')}</td>
          </tr>
        )}
      </tbody>
      <tfoot>
        <TasksListLabelsRow show_scheduled_at={showScheduledAt} />
      </tfoot>
    </table>
  );
}

TasksList.propTypes = {
  show_scheduled_at: PropTypes.bool,
  tasks: PropTypes.arrayOf(TasksListDataRow.propTypes.task).isRequired,
};

export { TasksList };
