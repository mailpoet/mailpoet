import React from 'react';
import PropTypes from 'prop-types';
import { Button } from '@wordpress/components';

import closeIcon from './close_icon.jsx';

const ModalHeader = ({
  title,
  onClose,
  headingId,
  isDismissible,
}) => (
  <div className="mailpoet-modal-header">
    <div className="mailpoet-modal-header-heading-container">
      { title && (
        <h1
          id={headingId}
          className="mailpoet-modal-header-heading"
        >
          { title }
        </h1>
      ) }
    </div>
    { isDismissible && (
      <Button onClick={onClose} icon={closeIcon} className="mailpoet-modal-close" />
    ) }
  </div>
);

ModalHeader.propTypes = {
  title: PropTypes.string,
  headingId: PropTypes.string,
  onClose: PropTypes.func,
  isDismissible: PropTypes.bool,
};

ModalHeader.defaultProps = {
  title: null,
  headingId: 'heading-id',
  onClose: () => {},
  isDismissible: true,
};

export default ModalHeader;
