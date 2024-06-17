import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';

function TasksListLabelsRow(props) {
  return (
    <tr>
      <th className="row-title">Id</th>
      <th className="row-title">{MailPoet.I18n.t('email')}</th>
      <th className="row-title">{MailPoet.I18n.t('subscriber')}</th>
      <th className="row-title">{MailPoet.I18n.t('priority')}</th>
      {props.show_scheduled_at ? (
        <th className="row-title">{MailPoet.I18n.t('scheduledAt')}</th>
      ) : null}
      {props.show_cancelled_at ? (
        <th className="row-title">{MailPoet.I18n.t('cancelledAt')}</th>
      ) : null}
      <th className="row-title">{MailPoet.I18n.t('updatedAt')}</th>
    </tr>
  );
}

TasksListLabelsRow.propTypes = {
  show_scheduled_at: PropTypes.bool,
  show_cancelled_at: PropTypes.bool,
};

TasksListLabelsRow.defaultProps = {
  show_scheduled_at: false,
  show_cancelled_at: false,
};

export { TasksListLabelsRow };
