import { MailPoet } from 'mailpoet';
import {
  TasksListDataRow,
  Props as TasksListDataRowProps,
} from './tasks-list-data-row';
import { TasksListLabelsRow } from './tasks-list-labels-row';

type Props = {
  tasks: TasksListDataRowProps['task'][];
  type: string;
};
function TasksList({ tasks, type }: Props): JSX.Element {
  let colsCount = 5;
  if (type === 'running') {
    colsCount += 1;
  }
  if (type === 'scheduled' || type === 'cancelled') {
    colsCount += 2;
  }

  return (
    <table className="widefat fixed striped">
      <thead>
        <TasksListLabelsRow type={type} />
      </thead>
      <tbody>
        {tasks.length ? (
          tasks.map((task) => (
            <TasksListDataRow key={task.id} task={task} type={type} />
          ))
        ) : (
          <tr className="mailpoet-listing-no-items">
            <td colSpan={colsCount}>{MailPoet.I18n.t('nothingToShow')}</td>
          </tr>
        )}
      </tbody>
      <tfoot>
        <TasksListLabelsRow type={type} />
      </tfoot>
    </table>
  );
}

export { TasksList };
