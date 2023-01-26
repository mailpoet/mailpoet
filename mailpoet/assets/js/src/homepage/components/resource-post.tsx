type Props = {
  link: string;
  abstract: string;
  title: string;
  imgSrc: string;
};

export function ResourcePost({
  link,
  abstract,
  title,
  imgSrc,
}: Props): JSX.Element {
  return (
    <div className="mailpoet-resource-post">
      <a href={link} target="_blank" rel="noreferrer">
        <img src={imgSrc} alt={title} width="292" height="166" />
      </a>
      <a href={link} target="_blank" rel="noreferrer">
        <h3>{title}</h3>
      </a>
      <a href={link} target="_blank" rel="noreferrer">
        {abstract}
      </a>
    </div>
  );
}
