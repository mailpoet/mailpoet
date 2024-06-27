import classNames from 'classnames';
import { automationRunStatusNames, automationStatusNames } from './names';
import { automationRunStatusClasses, automationStatusClasses } from './classes';

type StatusBadgeProps = {
  name: string;
  className: string;
};

type StatusProps = {
  status: string;
};

export function StatusBadge({
  name,
  className,
}: StatusBadgeProps): JSX.Element {
  return (
    <span
      className={classNames(
        'mailpoet-automation-status',
        `mailpoet-automation-status-${className}`,
      )}
    >
      {name}
    </span>
  );
}

export function AutomationStatus({ status }: StatusProps): JSX.Element {
  return (
    <StatusBadge
      name={automationStatusNames[status] ?? status}
      className={automationStatusClasses[status] ?? status}
    />
  );
}

export function AutomationRunStatus({ status }: StatusProps): JSX.Element {
  return (
    <StatusBadge
      name={automationRunStatusNames[status] ?? status}
      className={automationRunStatusClasses[status] ?? status}
    />
  );
}
