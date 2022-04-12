type Props = {
  title: string;
};

export function Badge({ title }: Props) {
  return <span className="mailpoet-badge">{title}</span>;
}
