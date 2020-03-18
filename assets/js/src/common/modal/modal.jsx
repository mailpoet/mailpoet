import React from 'react';
import { createPortal } from 'react-dom';
import PropTypes from 'prop-types';

import ModalFrame from './frame.jsx';
import ModalHeader from './header.jsx';

function Modal({
  onRequestClose,
  title,
  icon,
  closeButtonLabel,
  displayTitle,
  children,
  aria,
  isDismissible,
  shouldCloseOnEsc,
  shouldCloseOnClickOutside,
  role,
  contentClassName,
  contentLabel,
  overlayClassName,
  fullScreen,
}) {
  const headingId = aria.labelledby || 'mailpoet-modal-header';

  return createPortal(
    <ModalFrame
      onRequestClose={onRequestClose}
      aria={{
        labelledby: title ? headingId : null,
        describedby: aria.describedby,
      }}
      shouldCloseOnEsc={shouldCloseOnEsc}
      shouldCloseOnClickOutside={shouldCloseOnClickOutside}
      role={role}
      className={contentClassName}
      contentLabel={contentLabel}
      overlayClassName={overlayClassName}
      fullScreen={fullScreen}
    >
      <div
        className="mailpoet-modal-content"
        role="document"
      >
        {
          displayTitle && (
            <ModalHeader
              closeLabel={closeButtonLabel}
              headingId={headingId}
              icon={icon}
              isDismissible={isDismissible}
              onClose={onRequestClose}
              title={title}
            />
          )
        }
        { children }
      </div>
    </ModalFrame>,
    document.getElementById('mailpoet_modal')
  );
}

Modal.propTypes = {
  closeButtonLabel: PropTypes.string,
  children: PropTypes.node,
  aria: PropTypes.shape({
    labelledby: PropTypes.string,
    describedby: PropTypes.string,
  }),
  isDismissible: PropTypes.bool,
  contentClassName: PropTypes.string,
  contentLabel: PropTypes.string,
  overlayClassName: PropTypes.string,
  title: PropTypes.string,
  onRequestClose: PropTypes.func,
  displayTitle: PropTypes.bool,
  fullScreen: PropTypes.bool,
  focusOnMount: PropTypes.bool,
  shouldCloseOnEsc: PropTypes.bool,
  shouldCloseOnClickOutside: PropTypes.bool,
  role: PropTypes.string,
  icon: PropTypes.node,
};

Modal.defaultProps = {
  bodyOpenClassName: 'modal-open',
  onRequestClose: () => {},
  role: 'dialog',
  title: null,
  icon: null,
  aria: {},
  focusOnMount: true,
  shouldCloseOnEsc: true,
  shouldCloseOnClickOutside: true,
  isDismissible: true,
  displayTitle: true,
  fullScreen: false,
};

export default Modal;
