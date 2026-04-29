import { Heading } from '../typography/heading/heading';

type Props = {
  title: string;
  id?: string;
};

export function ModalHeader({ title, id = undefined }: Props) {
  return (
    <div className="mailpoet-modal-header">
      <Heading id={id} level={3}>
        {title}
      </Heading>
    </div>
  );
}
