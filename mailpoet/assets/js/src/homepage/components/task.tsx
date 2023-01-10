import { Icon } from '@wordpress/components';
import { check } from '@wordpress/icons';
import classnames from 'classnames';

type Props = {
  title: string;
  titleCompleted?: string;
  link: string;
  order: number;
  isCompleted: boolean;
  isActive: boolean;
  children?: React.ReactNode;
};

export function Task({
  title,
  titleCompleted = '',
  link,
  order,
  isCompleted,
  isActive,
  children = null,
}: Props): JSX.Element {
  const className = classnames('mailpoet-task-list__task', {
    'mailpoet-task-list__task--completed': isCompleted,
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
          {isCompleted ? <Icon icon={check} /> : order}
        </div>
      </div>
      <div className="mailpoet-task-list__task-content">
        <div className="mailpoet-task-list__task-title">
          {isCompleted && titleCompleted ? titleCompleted : title}
        </div>
        {children}
      </div>
    </li>
  );
}
