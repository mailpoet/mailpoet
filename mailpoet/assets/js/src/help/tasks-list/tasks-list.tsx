import { MailPoet } from 'mailpoet';
import {
  TasksListDataRow,
  Props as TasksListLabelsRowProps,
} from './tasks-list-data-row';
import { TasksListLabelsRow } from './tasks-list-labels-row';

type Props = {
  showScheduledAt?: boolean;
  showCancelledAt?: boolean;
  tasks: TasksListLabelsRowProps['task'][];
};
function TasksList({
  showScheduledAt = false,
  showCancelledAt = false,
  tasks,
}: Props): JSX.Element {
  const colsCount = showScheduledAt || showCancelledAt ? 7 : 5;

  return (
    <table className="widefat fixed striped">
      <thead>
        <TasksListLabelsRow
          showScheduledAt={showScheduledAt}
          showCancelledAt={showCancelledAt}
        />
      </thead>
      <tbody>
        {tasks.length ? (
          tasks.map((task) => (
            <TasksListDataRow
              key={task.id}
              task={task}
              showScheduledAt={showScheduledAt}
              showCancelledAt={showCancelledAt}
            />
          ))
        ) : (
          <tr className="mailpoet-listing-no-items">
            <td colSpan={colsCount}>{MailPoet.I18n.t('nothingToShow')}</td>
          </tr>
        )}
      </tbody>
      <tfoot>
        <TasksListLabelsRow
          showScheduledAt={showScheduledAt}
          showCancelledAt={showCancelledAt}
        />
      </tfoot>
    </table>
  );
}

export { TasksList };
