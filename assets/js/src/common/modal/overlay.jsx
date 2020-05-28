import React, { useEffect, useRef } from 'react';
import classnames from 'classnames';
import PropTypes from 'prop-types';

const ESCAPE = 27;

function ModalOverlay({
  onRequestClose,
  shouldCloseOnEsc,
  shouldCloseOnClickOutside,
  className,
  children,
}) {
  const overlayRef = useRef(null);

  // get focus on render so keys such as ESC work immediately
  useEffect(() => {
    overlayRef.current.focus();
  }, []);

  function onClose(event) {
    if (onRequestClose) {
      onRequestClose(event);
    }
  }

  function handleFocusOutside(event) {
    // filter only to clicks on overlay
    if (shouldCloseOnClickOutside && overlayRef.current === event.target) {
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
      ref={overlayRef}
      className={classnames(
        'mailpoet-modal-screen-overlay',
        className
      )}
      onKeyDown={handleKeyDown}
      onClick={handleFocusOutside}
      role="button"
      tabIndex="0"
    >
      {children}
    </div>
  );
}

ModalOverlay.propTypes = {
  onRequestClose: PropTypes.func,
  shouldCloseOnEsc: PropTypes.bool,
  shouldCloseOnClickOutside: PropTypes.bool,
  className: PropTypes.string,
  children: PropTypes.node.isRequired,
};

ModalOverlay.defaultProps = {
  onRequestClose: () => {},
  shouldCloseOnEsc: true,
  shouldCloseOnClickOutside: true,
  className: '',
};

export default ModalOverlay;
