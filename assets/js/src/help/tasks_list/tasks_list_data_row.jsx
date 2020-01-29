import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const TasksListDataRow = (props) => (
  <tr>
    <td className="column column-primary">
      { props.task.id }
    </td>
    <td className="column">
      { props.task.type }
    </td>
    <td className="column">
      { props.task.newsletter ? (
        <a
          href={props.task.newsletter.preview_url}
          data-newsletter-id={props.task.newsletter.newsletter_id}
          data-queue-id={props.task.newsletter.queue_id}
          target="_blank"
          rel="noopener noreferrer"
        >
          {props.task.newsletter.subject || MailPoet.I18n.t('preview')}
        </a>
      ) : MailPoet.I18n.t('none')}
    </td>
    <td className="column">
      { props.task.priority }
    </td>
    { props.show_scheduled_at ? (
      <td className="column-date">
        <abbr>{ MailPoet.Date.format(props.task.scheduled_at * 1000) }</abbr>
      </td>
    ) : null }
    <td className="column-date">
      <abbr>{ MailPoet.Date.format(props.task.updated_at * 1000) }</abbr>
    </td>
  </tr>
);

TasksListDataRow.propTypes = {
  show_scheduled_at: PropTypes.bool,
  task: PropTypes.shape({
    id: PropTypes.number.isRequired,
    type: PropTypes.string.isRequired,
    priority: PropTypes.number.isRequired,
    updated_at: PropTypes.number.isRequired,
    scheduled_at: PropTypes.number,
    status: PropTypes.string,
    newsletter: PropTypes.shape({
      newsletter_id: PropTypes.number.isRequired,
      queue_id: PropTypes.number.isRequired,
      preview_url: PropTypes.string.isRequired,
      subject: PropTypes.string,
    }),
  }).isRequired,
};

TasksListDataRow.defaultProps = {
  show_scheduled_at: false,
};

export default TasksListDataRow;
