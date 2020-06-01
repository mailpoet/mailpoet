import React from 'react';
import { createPortal } from 'react-dom';

import ModalFrame from './frame';
import ModalHeader from './header';
import ModalOverlay from './overlay';
import ModalCloseIcon from './close_icon';

type Props = {
  isDismissible?: boolean,
  contentClassName?: string,
  overlayClassName?: string,
  title?: string,
  onRequestClose?: () => void,
  fullScreen?: boolean,
  shouldCloseOnEsc?: boolean,
  shouldCloseOnClickOutside?: boolean,
  children: React.ReactNode,
};

function Modal({
  onRequestClose,
  title,
  children,
  isDismissible,
  shouldCloseOnEsc,
  shouldCloseOnClickOutside,
  contentClassName,
  overlayClassName,
  fullScreen,
}: Props) {
  return createPortal(
    <ModalOverlay
      onRequestClose={onRequestClose}
      shouldCloseOnEsc={shouldCloseOnEsc}
      shouldCloseOnClickOutside={shouldCloseOnClickOutside}
      className={overlayClassName}
    >
      <ModalFrame
        className={contentClassName}
        fullScreen={fullScreen}
      >
        { title && (
          <ModalHeader title={title} />
        ) }
        { isDismissible && (
          <button type="button" onClick={onRequestClose} className="mailpoet-modal-close">{ModalCloseIcon}</button>
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

Modal.defaultProps = {
  onRequestClose: () => {},
  title: null,
  shouldCloseOnEsc: true,
  shouldCloseOnClickOutside: true,
  isDismissible: true,
  fullScreen: false,
};

export default Modal;
