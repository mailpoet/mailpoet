type Props = {
  title: string;
  link: string;
  imgSrc: string;
  description?: string;
};

export function DiscoveryTask({
  title,
  link,
  description,
  imgSrc,
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
      <h3>{`${title} →`}</h3>
      {description ? <p>{description}</p> : null}
    </li>
  );
}
