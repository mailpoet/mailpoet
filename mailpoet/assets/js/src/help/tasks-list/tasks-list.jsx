import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { TasksListDataRow } from './tasks-list-data-row.jsx';
import { TasksListLabelsRow } from './tasks-list-labels-row.jsx';

function TasksList(props) {
  const colsCount = props.show_scheduled_at ? 5 : 4;

  return (
    <table className="widefat fixed striped">
      <thead>
        <TasksListLabelsRow show_scheduled_at={props.show_scheduled_at} />
      </thead>
      <tbody>
        {props.tasks.length ? (
          props.tasks.map((task) => (
            <TasksListDataRow
              key={task.id}
              task={task}
              show_scheduled_at={props.show_scheduled_at}
            />
          ))
        ) : (
          <tr className="mailpoet-listing-no-items">
            <td colSpan={colsCount}>{MailPoet.I18n.t('nothingToShow')}</td>
          </tr>
        )}
      </tbody>
      <tfoot>
        <TasksListLabelsRow show_scheduled_at={props.show_scheduled_at} />
      </tfoot>
    </table>
  );
}

TasksList.propTypes = {
  show_scheduled_at: PropTypes.bool,
  tasks: PropTypes.arrayOf(TasksListDataRow.propTypes.task).isRequired,
};

TasksList.defaultProps = {
  show_scheduled_at: false,
};

export { TasksList };
