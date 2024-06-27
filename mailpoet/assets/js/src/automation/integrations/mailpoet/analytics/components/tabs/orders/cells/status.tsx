import { StatusBadge } from '../../../../../../../components/status';
import {
  ClassProps,
  ERROR_CLASS,
  SUCCESS_CLASS,
  WARNING_CLASS,
} from '../../../../../../../components/status/classes';

export const orderStatusClasses: ClassProps = {
  processing: SUCCESS_CLASS,
  'on-hold': WARNING_CLASS,
  failed: ERROR_CLASS,
  trash: ERROR_CLASS,
};

type OrderStatusProps = {
  name: string;
  status: string;
};
export function StatusCell({ status, name }: OrderStatusProps): JSX.Element {
  return (
    <StatusBadge name={name} className={orderStatusClasses[status] ?? status} />
  );
}
