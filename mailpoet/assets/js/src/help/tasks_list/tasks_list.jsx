import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import TaskListDataRow from './tasks_list_data_row.jsx';
import TaskListLabelsRow from './tasks_list_labels_row.jsx';

function TasksList(props) {
  const colsCount = props.show_scheduled_at ? 6 : 5;

  return (
    <table className="widefat fixed striped">
      <thead>
        <TaskListLabelsRow show_scheduled_at={props.show_scheduled_at} />
      </thead>
      <tbody>
        {props.tasks.length ? (
          props.tasks.map((task) => (
            <TaskListDataRow
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
        <TaskListLabelsRow show_scheduled_at={props.show_scheduled_at} />
      </tfoot>
    </table>
  );
}

TasksList.propTypes = {
  show_scheduled_at: PropTypes.bool,
  tasks: PropTypes.arrayOf(TaskListDataRow.propTypes.task).isRequired,
};

TasksList.defaultProps = {
  show_scheduled_at: false,
};

export default TasksList;
