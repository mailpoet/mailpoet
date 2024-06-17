import { MailPoet } from 'mailpoet';

type Props = {
  showScheduledAt?: boolean;
  showCancelledAt?: boolean;
};

function TasksListLabelsRow({
  showScheduledAt = false,
  showCancelledAt = false,
}: Props): JSX.Element {
  return (
    <tr>
      <th className="row-title">Id</th>
      <th className="row-title">{MailPoet.I18n.t('email')}</th>
      <th className="row-title">{MailPoet.I18n.t('subscriber')}</th>
      <th className="row-title">{MailPoet.I18n.t('priority')}</th>
      {showScheduledAt ? (
        <th className="row-title">{MailPoet.I18n.t('scheduledAt')}</th>
      ) : null}
      {showCancelledAt ? (
        <th className="row-title">{MailPoet.I18n.t('cancelledAt')}</th>
      ) : null}
      <th className="row-title">{MailPoet.I18n.t('updatedAt')}</th>
      {showScheduledAt || showCancelledAt ? (
        <th className="row-title">{MailPoet.I18n.t('action')}</th>
      ) : null}
    </tr>
  );
}

export { TasksListLabelsRow };
