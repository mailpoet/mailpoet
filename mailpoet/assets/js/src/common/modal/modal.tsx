import { ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { noop } from 'lodash';

import ModalFrame from './frame';
import ModalHeader from './header';
import ModalOverlay from './overlay';
import ModalCloseIcon from './close_icon';

type Props = {
  title?: string;
  isDismissible?: boolean;
  shouldCloseOnEsc?: boolean;
  shouldCloseOnClickOutside?: boolean;
  onRequestClose?: () => void;
  fullScreen?: boolean;
  contentClassName?: string;
  overlayClassName?: string;
  children: ReactNode;
};

function Modal({
  title = null,
  isDismissible = true,
  shouldCloseOnEsc = true,
  shouldCloseOnClickOutside = true,
  onRequestClose = noop,
  fullScreen = false,
  contentClassName = '',
  overlayClassName = '',
  children,
}: Props) {
  return createPortal(
    <ModalOverlay
      isDismissible={isDismissible}
      onRequestClose={onRequestClose}
      shouldCloseOnEsc={shouldCloseOnEsc}
      shouldCloseOnClickOutside={shouldCloseOnClickOutside}
      className={overlayClassName}
    >
      <ModalFrame className={contentClassName} fullScreen={fullScreen}>
        {title && <ModalHeader title={title} />}
        {isDismissible && (
          <button
            type="button"
            onClick={onRequestClose}
            className="mailpoet-modal-close"
            data-automation-id="mailpoet-modal-close"
          >
            {ModalCloseIcon}
          </button>
        )}
        <div className="mailpoet-modal-content" role="document">
          {children}
        </div>
      </ModalFrame>
    </ModalOverlay>,
    document.getElementById('mailpoet-modal'),
  );
}

export default Modal;
