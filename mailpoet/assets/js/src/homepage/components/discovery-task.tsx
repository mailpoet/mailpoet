import { Icon } from '@wordpress/components';
import { check } from '@wordpress/icons';
import classnames from 'classnames';
import { trackCtaAndRedirect } from 'homepage/tracking';

type Props = {
  title: string;
  slug: string;
  link: string;
  imgSrc: string;
  isDone: boolean;
  doneMessage: string;
  description?: string;
};

export function DiscoveryTask({
  title,
  slug,
  link,
  description,
  doneMessage,
  imgSrc,
  isDone,
}: Props): JSX.Element {
  const handleTaskClick = () => {
    trackCtaAndRedirect('Home Page Task', slug, link);
  };
  return (
    <li
      className={classnames('mailpoet-product-discovery__task', {
        'mailpoet-product-discovery__task--completed': isDone,
      })}
      role="row"
      onClick={isDone ? undefined : handleTaskClick}
      tabIndex={isDone ? undefined : 0}
      onKeyDown={
        isDone ? undefined : (e) => e.key === 'Enter' && handleTaskClick()
      }
    >
      <img src={imgSrc} alt={title} width={124} height={72} />
      <div className="mailpoet-product-discovery__task-content">
        {isDone ? (
          <h3>{doneMessage}</h3>
        ) : (
          <>
            <h3>{`${title} →`}</h3>
            {description && <p>{description}</p>}
          </>
        )}
      </div>
      <div className="mailpoet-product-discovery__task-after">
        {isDone && (
          <div className="mailpoet-task-list__task-icon">
            <Icon icon={check} />
          </div>
        )}
      </div>
    </li>
  );
}
