import React, { useRef } from 'react';
import classnames from 'classnames';
import PropTypes from 'prop-types';

const ESCAPE = 27;

function ModalFrame({
  shouldCloseOnClickOutside,
  onRequestClose,
  shouldCloseOnEsc,
  overlayClassName,
  children,
  className,
  role,
  fullScreen,
}) {
  const wrapperRef = useRef(null);
  function onClose(event) {
    if (onRequestClose) {
      onRequestClose(event);
    }
  }

  function handleFocusOutside(event) {
    if (shouldCloseOnClickOutside
      && wrapperRef.current
      && !wrapperRef.current.contains(event.target) // filter clicks inside the modal window
    ) {
      onClose(event);
    }
  }

  function handleEscapeKeyDown(event) {
    if (shouldCloseOnEsc) {
      event.stopPropagation();
      onClose(event);
    }
  }

  function handleKeyDown(event) {
    if (event.keyCode === ESCAPE) {
      handleEscapeKeyDown(event);
    }
  }

  return (
    <div
      className={classnames(
        'mailpoet-modal-screen-overlay',
        overlayClassName
      )}
      onKeyDown={handleKeyDown}
      onClick={handleFocusOutside}
      role="button"
      tabIndex="0"
    >
      <div
        ref={wrapperRef}
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
    </div>
  );
}

ModalFrame.propTypes = {
  onRequestClose: PropTypes.func,
  shouldCloseOnEsc: PropTypes.bool,
  fullScreen: PropTypes.bool,
  shouldCloseOnClickOutside: PropTypes.bool,
  role: PropTypes.string,
  className: PropTypes.string,
  overlayClassName: PropTypes.string,
  children: PropTypes.node.isRequired,
};

ModalFrame.defaultProps = {
  onRequestClose: () => {},
  role: 'dialog',
  shouldCloseOnEsc: true,
  fullScreen: false,
  shouldCloseOnClickOutside: true,
  className: '',
  overlayClassName: '',
};

export default ModalFrame;
