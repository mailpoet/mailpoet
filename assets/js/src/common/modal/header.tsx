import React from 'react';

import Heading from '../typography/heading/heading';

type Props = {
  title: string,
};

const ModalHeader = ({ title }: Props) => (
  <div className="mailpoet-modal-header">
    <Heading level={3}>
      { title }
    </Heading>
  </div>
);

export default ModalHeader;
