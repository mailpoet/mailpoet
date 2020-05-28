import React from 'react';
import PropTypes from 'prop-types';

import closeIcon from './close_icon.jsx';
import Heading from 'common/typography/heading/heading';

const ModalHeader = ({
  title,
  onClose,
  isDismissible,
}) => (
  <div className="mailpoet-modal-header">
    <div className="mailpoet-modal-header-heading-container">
      <Heading level={3}>
        { title }
      </Heading>
    </div>
    { isDismissible && (
      <button type="button" onClick={onClose} className="mailpoet-modal-close">{closeIcon}</button>
    ) }
  </div>
);

ModalHeader.propTypes = {
  title: PropTypes.string.isRequired,
  onClose: PropTypes.func,
  isDismissible: PropTypes.bool,
};

ModalHeader.defaultProps = {
  onClose: () => {},
  isDismissible: true,
};

export default ModalHeader;
