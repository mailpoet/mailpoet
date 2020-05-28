import React from 'react';
import { createPortal } from 'react-dom';
import PropTypes from 'prop-types';

import ModalFrame from './frame.jsx';
import ModalHeader from './header.jsx';
import ModalOverlay from './overlay.jsx';
import closeIcon from './close_icon';

function Modal({
  onRequestClose,
  title,
  children,
  isDismissible,
  shouldCloseOnEsc,
  shouldCloseOnClickOutside,
  role,
  contentClassName,
  overlayClassName,
  fullScreen,
}) {
  return createPortal(
    <ModalOverlay
      onRequestClose={onRequestClose}
      shouldCloseOnEsc={shouldCloseOnEsc}
      shouldCloseOnClickOutside={shouldCloseOnClickOutside}
      className={overlayClassName}
    >
      <ModalFrame
        role={role}
        className={contentClassName}
        fullScreen={fullScreen}
      >
        { title && (
          <ModalHeader title={title} />
        ) }
        { isDismissible && (
          <button type="button" onClick={onRequestClose} className="mailpoet-modal-close">{closeIcon}</button>
        ) }
        <div
          className="mailpoet-modal-content"
          role="document"
        >
          { children }
        </div>
      </ModalFrame>
    </ModalOverlay>,
    document.getElementById('mailpoet-modal')
  );
}

Modal.propTypes = {
  children: PropTypes.node,
  isDismissible: PropTypes.bool,
  contentClassName: PropTypes.string,
  overlayClassName: PropTypes.string,
  title: PropTypes.string,
  onRequestClose: PropTypes.func,
  fullScreen: PropTypes.bool,
  shouldCloseOnEsc: PropTypes.bool,
  shouldCloseOnClickOutside: PropTypes.bool,
  role: PropTypes.string,
};

Modal.defaultProps = {
  bodyOpenClassName: 'modal-open',
  onRequestClose: () => {},
  role: 'dialog',
  title: null,
  shouldCloseOnEsc: true,
  shouldCloseOnClickOutside: true,
  isDismissible: true,
  fullScreen: false,
};

export default Modal;
