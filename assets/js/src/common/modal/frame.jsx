import React from 'react';
import classnames from 'classnames';
import PropTypes from 'prop-types';

function ModalFrame({
  children,
  className,
  role,
  fullScreen,
}) {
  return (
    <div
      className={classnames(
        'mailpoet-modal-frame',
        { 'mailpoet-modal-full-screen': fullScreen },
        className
      )}
      role={role}
      tabIndex="-1"
    >
      {children}
    </div>
  );
}

ModalFrame.propTypes = {
  fullScreen: PropTypes.bool,
  role: PropTypes.string,
  className: PropTypes.string,
  children: PropTypes.node.isRequired,
};

ModalFrame.defaultProps = {
  role: 'dialog',
  fullScreen: false,
  className: '',
};

export default ModalFrame;
