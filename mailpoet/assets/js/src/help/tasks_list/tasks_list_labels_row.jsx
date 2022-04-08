import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

function TasksListLabelsRow(props) {
  return (
    <tr>
      <th className="row-title">Id</th>
      <th className="row-title">{MailPoet.I18n.t('type')}</th>
      <th className="row-title">{MailPoet.I18n.t('email')}</th>
      <th className="row-title">{MailPoet.I18n.t('priority')}</th>
      {props.show_scheduled_at ? (
        <th className="row-title">{MailPoet.I18n.t('scheduledAt')}</th>
      ) : null}
      <th className="row-title">{MailPoet.I18n.t('updatedAt')}</th>
    </tr>
  );
}

TasksListLabelsRow.propTypes = {
  show_scheduled_at: PropTypes.bool,
};

TasksListLabelsRow.defaultProps = {
  show_scheduled_at: false,
};

export default TasksListLabelsRow;
