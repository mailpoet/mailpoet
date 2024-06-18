import { MailPoet } from 'mailpoet';

type Props = {
  type: string;
};

function TasksListLabelsRow({ type }: Props): JSX.Element {
  const hasAction = ['scheduled', 'running', 'cancelled'].includes(type);
  return (
    <tr>
      <th className="row-title">Id</th>
      <th className="row-title">{MailPoet.I18n.t('email')}</th>
      <th className="row-title">{MailPoet.I18n.t('subscriber')}</th>
      <th className="row-title">{MailPoet.I18n.t('priority')}</th>
      {type === 'scheduled' ? (
        <th className="row-title">{MailPoet.I18n.t('scheduledAt')}</th>
      ) : null}
      {type === 'cancelled' ? (
        <th className="row-title">{MailPoet.I18n.t('cancelledAt')}</th>
      ) : null}
      <th className="row-title">{MailPoet.I18n.t('updatedAt')}</th>
      {hasAction ? (
        <th className="row-title">{MailPoet.I18n.t('action')}</th>
      ) : null}
    </tr>
  );
}

export { TasksListLabelsRow };
