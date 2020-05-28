import React from 'react';
import PropTypes from 'prop-types';

import Heading from 'common/typography/heading/heading';

const ModalHeader = ({ title }) => (
  <div className="mailpoet-modal-header">
    <div className="mailpoet-modal-header-heading-container">
      <Heading level={3}>
        { title }
      </Heading>
    </div>
  </div>
);

ModalHeader.propTypes = {
  title: PropTypes.string.isRequired,
};

export default ModalHeader;
