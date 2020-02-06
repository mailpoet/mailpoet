import React from 'react';
import PropTypes from 'prop-types';
import { Button } from '@wordpress/components';

import closeIcon from './close_icon.jsx';

const ModalHeader = ({
  icon,
  title,
  onClose,
  closeLabel,
  headingId,
  isDismissible,
}) => (
  <div className="mailpoet-modal-header">
    <div className="mailpoet-modal-header-heading-container">
      { icon && (
        <span
          className="mailpoet-modal-icon-container"
          aria-hidden
        >
          { icon }
        </span>
      ) }
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
      <Button onClick={onClose} icon={closeIcon} label={closeLabel} className="mailpoet-modal-close" />
    ) }
  </div>
);

ModalHeader.propTypes = {
  title: PropTypes.string,
  headingId: PropTypes.string,
  onClose: PropTypes.func,
  closeLabel: PropTypes.string,
  icon: PropTypes.node,
  isDismissible: PropTypes.bool,
};

ModalHeader.defaultProps = {
  title: null,
  headingId: 'heading-id',
  onClose: () => {},
  closeLabel: '',
  icon: null,
  isDismissible: true,
};

export default ModalHeader;
