import { Icon } from '@wordpress/components';
import { check } from '@wordpress/icons';
import classnames from 'classnames';

type Props = {
  title: string;
  link: string;
  order: number;
  status: boolean;
};

export function Task({ title, link, order, status }: Props): JSX.Element {
  const className = classnames('mailpoet-task-list__task', {
    'mailpoet-task-list__task--completed': status,
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
      <div className="mailpoet-task-list__task-title">{title}</div>
    </li>
  );
}
