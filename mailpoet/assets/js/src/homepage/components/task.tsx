import { Icon } from '@wordpress/components';
import { check } from '@wordpress/icons';
import classnames from 'classnames';
import { trackCtaAndRedirect } from 'homepage/tracking';

type Props = {
  title: string;
  titleCompleted?: string;
  slug: string;
  link: string;
  order: number;
  isCompleted: boolean;
  isActive: boolean;
  children?: React.ReactNode;
};

export function Task({
  title,
  titleCompleted = '',
  slug,
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
    trackCtaAndRedirect('Home Page Task', slug, link);
  };
  return (
    <li
      className={className}
      role="row"
      onClick={isCompleted ? undefined : handleTaskClick}
      tabIndex={isCompleted ? undefined : 0}
      onKeyDown={
        isCompleted ? undefined : (e) => e.key === 'Enter' && handleTaskClick()
      }
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
