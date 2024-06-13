import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import parseDate from 'date-fns/parse';

function TasksListDataRow({
  task,
  show_scheduled_at: showScheduledAt = false,
}) {
  let scheduled = task.scheduled_at;
  if (scheduled) {
    scheduled = parseDate(scheduled, 'yyyy-MM-dd HH:mm:ss', new Date());
  }

  const updated = parseDate(task.updated_at, 'yyyy-MM-dd HH:mm:ss', new Date());

  return (
    <tr>
      <td className="column column-primary">{task.id}</td>
      <td className="column">{task.type}</td>
      <td className="column">
        {task.newsletter ? (
          <a
            href={task.newsletter.preview_url}
            data-newsletter-id={task.newsletter.newsletter_id}
            data-queue-id={task.newsletter.queue_id}
            target="_blank"
            rel="noopener noreferrer"
          >
            {task.newsletter.subject || MailPoet.I18n.t('preview')}
          </a>
        ) : (
          MailPoet.I18n.t('none')
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
      <td className="column-date">
        <abbr>{`${MailPoet.Date.short(updated)} ${MailPoet.Date.time(
          updated,
        )}`}</abbr>
      </td>
    </tr>
  );
}

TasksListDataRow.propTypes = {
  show_scheduled_at: PropTypes.bool,
  task: PropTypes.shape({
    id: PropTypes.number.isRequired,
    type: PropTypes.string.isRequired,
    priority: PropTypes.number.isRequired,
    updated_at: PropTypes.string.isRequired,
    scheduled_at: PropTypes.string,
    status: PropTypes.string,
    newsletter: PropTypes.shape({
      newsletter_id: PropTypes.number.isRequired,
      queue_id: PropTypes.number.isRequired,
      preview_url: PropTypes.string.isRequired,
      subject: PropTypes.string,
    }),
  }).isRequired,
};

export { TasksListDataRow };
