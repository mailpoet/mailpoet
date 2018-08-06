import React from 'react';
import MailPoet from 'mailpoet';

const TasksListDataRow = props => (
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
        >
          {props.task.newsletter.subject || MailPoet.I18n.t('preview')}
        </a>) : MailPoet.I18n.t('none')
      }
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
  show_scheduled_at: React.PropTypes.bool,
  task: React.PropTypes.shape({
    id: React.PropTypes.number.isRequired,
    type: React.PropTypes.string.isRequired,
    priority: React.PropTypes.number.isRequired,
    updated_at: React.PropTypes.number.isRequired,
    scheduled_at: React.PropTypes.number,
    status: React.PropTypes.string,
    newsletter: React.PropTypes.shape({
      newsletter_id: React.PropTypes.number.isRequired,
      queue_id: React.PropTypes.number.isRequired,
      preview_url: React.PropTypes.string.isRequired,
      subject: React.PropTypes.string,
    }),
  }).isRequired,
};

TasksListDataRow.defaultProps = {
  show_scheduled_at: false,
  task: {
    newsletter: null,
  },
};

module.exports = TasksListDataRow;
