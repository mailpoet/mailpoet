type Props = {
  title: string;
  link: string;
  description?: string;
};

export function DiscoveryTask({
  title,
  link,
  description,
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
      <h3>{`${title} →`}</h3>
      {description ? <p>{description}</p> : null}
    </li>
  );
}
