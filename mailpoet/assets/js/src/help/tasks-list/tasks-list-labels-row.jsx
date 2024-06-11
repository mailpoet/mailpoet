import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';

function TasksListLabelsRow({ show_scheduled_at: showScheduledAt = false }) {
  return (
    <tr>
      <th className="row-title">Id</th>
      <th className="row-title">{MailPoet.I18n.t('email')}</th>
      <th className="row-title">{MailPoet.I18n.t('priority')}</th>
      {showScheduledAt ? (
        <th className="row-title">{MailPoet.I18n.t('scheduledAt')}</th>
      ) : null}
      <th className="row-title">{MailPoet.I18n.t('updatedAt')}</th>
    </tr>
  );
}

TasksListLabelsRow.propTypes = {
  show_scheduled_at: PropTypes.bool,
};

export { TasksListLabelsRow };
