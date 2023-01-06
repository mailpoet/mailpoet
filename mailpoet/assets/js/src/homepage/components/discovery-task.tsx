import { Icon } from '@wordpress/components';
import { check } from '@wordpress/icons';

type Props = {
  title: string;
  link: string;
  imgSrc: string;
  isDone: boolean;
  doneMessage: string;
  description?: string;
};

export function DiscoveryTask({
  title,
  link,
  description,
  doneMessage,
  imgSrc,
  isDone,
}: Props): JSX.Element {
  const handleTaskClick = () => {
    window.location.href = link;
  };
  return (
    <li
      className="mailpoet-product-discovery__task"
      role="row"
      onClick={handleTaskClick}
      tabIndex={0}
      onKeyDown={(e) => e.key === 'Enter' && handleTaskClick()}
    >
      <img src={imgSrc} alt={title} width={124} height={72} />
      <div className="mailpoet-product-discovery__task-content">
        {isDone ? (
          <h3>{doneMessage}</h3>
        ) : (
          <>
            <h3>{`${title} →`}</h3>
            {description ? <p>{description}</p> : null}
          </>
        )}
      </div>
      <div className="mailpoet-product-discovery__task-after">
        {isDone ? (
          <div className="mailpoet-task-list__task-icon">
            <Icon icon={check} />
          </div>
        ) : null}
      </div>
    </li>
  );
}
