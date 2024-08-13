import { MailPoet } from 'mailpoet';
import { CancelTaskButton, RescheduleTaskButton } from './tasks-list-actions';

export type Props = {
  type: string;
  task: {
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
};

function TasksListDataRow({ type, task }: Props): JSX.Element {
  const showScheduledAt = type === 'scheduled';
  const showCancelledAt = type === 'cancelled';
  const canCancelTask = type === 'scheduled' || type === 'running';
  const canRescheduleTask = type === 'cancelled';

  return (
    <tr>
      <td className="column column-primary">{task.id}</td>
      <td className="column">
        {task.newsletter ? (
          <a
            href={task.newsletter.previewUrl}
            data-newsletter-id={task.newsletter.newsletterId}
            data-queue-id={task.newsletter.queueId}
            target="_blank"
            rel="noopener noreferrer"
          >
            {task.newsletter.subject || MailPoet.I18n.t('preview')}
          </a>
        ) : (
          MailPoet.I18n.t('none')
        )}
      </td>
      <td className="column">
        {task.subscriberEmail ? (
          <a
            href={`admin.php?page=mailpoet-subscribers#/search[${task.subscriberEmail}]`}
          >
            {task.subscriberEmail}
          </a>
        ) : (
          <i>{MailPoet.I18n.t('multipleSubscribers')}</i>
        )}
      </td>
      <td className="column">{task.priority}</td>
      {showScheduledAt ? (
        <td className="column-date">
          <abbr>{`${MailPoet.Date.short(task.scheduledAt)} ${MailPoet.Date.time(
            task.scheduledAt,
          )}`}</abbr>
        </td>
      ) : null}
      {showCancelledAt ? (
        <td className="column-date">
          <abbr>{`${MailPoet.Date.short(task.cancelledAt)} ${MailPoet.Date.time(
            task.cancelledAt,
          )}`}</abbr>
        </td>
      ) : null}
      <td className="column-date">
        <abbr>{`${MailPoet.Date.short(task.updatedAt)} ${MailPoet.Date.time(
          task.updatedAt,
        )}`}</abbr>
      </td>
      {canCancelTask ? (
        <td>
          <CancelTaskButton task={task} />
        </td>
      ) : null}
      {canRescheduleTask ? (
        <td>
          <RescheduleTaskButton task={task} />
        </td>
      ) : null}
    </tr>
  );
}

export { TasksListDataRow };
