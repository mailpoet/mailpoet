import Heading from '../typography/heading/heading';

type Props = {
  title: string;
};

function ModalHeader({ title }: Props) {
  return (
    <div className="mailpoet-modal-header">
      <Heading level={3}>{title}</Heading>
    </div>
  );
}

export default ModalHeader;
