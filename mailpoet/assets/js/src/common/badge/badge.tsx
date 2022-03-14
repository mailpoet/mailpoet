type Props = {
  title: string;
};

function Badge({ title }: Props) {
  return <span className="mailpoet-badge">{title}</span>;
}

export default Badge;
