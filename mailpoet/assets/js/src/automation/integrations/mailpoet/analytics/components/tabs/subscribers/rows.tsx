import { SubscriberData } from '../../../store';
import { CustomerCell } from '../orders/cells/customer';
import { MailPoet } from '../../../../../../../mailpoet';
import { StatusCell } from './cells/status';
import { StepCell } from './cells/step';

export function transformSubscribersToRows(
  subscribers: SubscriberData[] | undefined,
) {
  return subscribers === undefined
    ? []
    : subscribers.map((subscriber) => [
        {
          display: <CustomerCell customer={subscriber.subscriber} />,
          value: subscriber.subscriber.last_name,
        },
        {
          display: <StepCell step={subscriber.run.step} />,
          value: subscriber.run.step.name,
        },
        {
          display: <StatusCell status={subscriber.run.status} />,
          value: subscriber.run.status,
        },
        {
          display: MailPoet.Date.format(new Date(subscriber.date)),
          value: subscriber.date,
        },
      ]);
}
