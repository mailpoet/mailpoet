import { Button } from '@wordpress/components';
import { MailPoet } from 'mailpoet';

function TaskButton({ id, type }: { id: number, type: string }): JSX.Element {
  return (
    <Button variant="secondary" size="small" isDestructive={type === 'cancel'}>
      {MailPoet.I18n.t(`${type}Action`)}
    </Button>
  );
}

export function CancelTaskButton({ id }: { id: number }): JSX.Element {
  return <TaskButton id={id} type="cancel" />;
}

export function RescheduleTaskButton({ id }: { id: number }): JSX.Element {
  return <TaskButton id={id} type="reschedule" />;
}
