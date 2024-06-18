import { MailPoet } from 'mailpoet';
import parseDate from 'date-fns/parse';
import { CancelTaskButton, RescheduleTaskButton } from './tasks-list-actions';

export type Props = {
  showScheduledAt: boolean;
  showCancelledAt: boolean;
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

function TasksListDataRow({
  showScheduledAt = false,
  showCancelledAt = false,
  task,
}: Props): JSX.Element {
  let scheduled: Date;
  if (showScheduledAt) {
    scheduled = parseDate(task.scheduledAt, 'yyyy-MM-dd HH:mm:ss', new Date());
  }

  let cancelled: Date;
  if (showCancelledAt) {
    cancelled = parseDate(task.cancelledAt, 'yyyy-MM-dd HH:mm:ss', new Date());
  }

  const updated: Date = parseDate(
    task.updatedAt,
    'yyyy-MM-dd HH:mm:ss',
    new Date(),
  );

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
          <abbr>{`${MailPoet.Date.short(scheduled)} ${MailPoet.Date.time(
            scheduled,
          )}`}</abbr>
        </td>
      ) : null}
      {showCancelledAt ? (
        <td className="column-date">
          <abbr>{`${MailPoet.Date.short(cancelled)} ${MailPoet.Date.time(
            cancelled,
          )}`}</abbr>
        </td>
      ) : null}
      <td className="column-date">
        <abbr>{`${MailPoet.Date.short(updated)} ${MailPoet.Date.time(
          updated,
        )}`}</abbr>
      </td>
      {showScheduledAt ? (
        <td>
          <CancelTaskButton task={task} />
        </td>
      ) : null}
      {showCancelledAt ? (
        <td>
          <RescheduleTaskButton task={task} />
        </td>
      ) : null}
    </tr>
  );
}

export { TasksListDataRow };
