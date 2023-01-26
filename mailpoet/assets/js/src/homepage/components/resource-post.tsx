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
    <a
      className="mailpoet-resource-post"
      href={link}
      target="_blank"
      rel="noreferrer"
    >
      <img src={imgSrc} alt={title} width="292" height="166" />
      <h3>{title}</h3>
      <p>{abstract}</p>
    </a>
  );
}
