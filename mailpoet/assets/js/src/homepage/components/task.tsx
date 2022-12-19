import { Icon } from '@wordpress/components';
import { check } from '@wordpress/icons';
import classnames from 'classnames';

type Props = {
  title: string;
  titleCompleted?: string;
  link: string;
  order: number;
  status: boolean;
  isActive: boolean;
  children?: React.ReactNode;
};

export function Task({
  title,
  titleCompleted = '',
  link,
  order,
  status,
  isActive,
  children = null,
}: Props): JSX.Element {
  const className = classnames('mailpoet-task-list__task', {
    'mailpoet-task-list__task--completed': status,
    'mailpoet-task-list__task--active': isActive,
  });
  const handleTaskClick = () => {
    window.location.href = link;
  };
  return (
    <li
      className={className}
      role="row"
      onClick={handleTaskClick}
      tabIndex={0}
      onKeyDown={(e) => e.key === 'Enter' && handleTaskClick()}
    >
      <div className="mailpoet-task-list__task-before">
        <div className="mailpoet-task-list__task-icon">
          {status ? <Icon icon={check} /> : order}
        </div>
      </div>
      <div className="mailpoet-task-list__task-content">
        <div className="mailpoet-task-list__task-title">
          {status && titleCompleted ? titleCompleted : title}
        </div>
        {children}
      </div>
    </li>
  );
}
