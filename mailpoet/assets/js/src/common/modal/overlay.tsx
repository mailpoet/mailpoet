import React, { useEffect, useRef } from 'react';
import classnames from 'classnames';
import { noop } from 'lodash';

const ESCAPE = 27;

type Props = {
  isDismissible?: boolean;
  shouldCloseOnEsc?: boolean;
  shouldCloseOnClickOutside?: boolean;
  onRequestClose?: (event: React.SyntheticEvent) => void;
  className?: string;
  children: React.ReactNode;
};

function ModalOverlay({
  isDismissible = true,
  shouldCloseOnEsc = true,
  shouldCloseOnClickOutside = true,
  onRequestClose = noop,
  className = '',
  children,
}: Props) {
  const overlayRef = useRef(null);

  // get focus on render so keys such as ESC work immediately
  useEffect(() => {
    overlayRef.current.focus();
  }, []);

  function onClose(event: React.SyntheticEvent) {
    if (onRequestClose) {
      onRequestClose(event);
    }
  }

  function handleFocusOutside(event: React.MouseEvent) {
    // filter only to clicks on overlay
    if (shouldCloseOnClickOutside && overlayRef.current === event.target) {
      onClose(event);
    }
  }

  function handleEscapeKeyDown(event: React.KeyboardEvent) {
    if (shouldCloseOnEsc) {
      event.stopPropagation();
      onClose(event);
    }
  }

  function handleKeyDown(event: React.KeyboardEvent) {
    if (event.keyCode === ESCAPE) {
      handleEscapeKeyDown(event);
    }
  }

  return (
    <div
      ref={overlayRef}
      className={classnames(
        'mailpoet-modal-screen-overlay',
        isDismissible ? 'mailpoet-modal-is-dismissible' : null,
        className
      )}
      onKeyDown={handleKeyDown}
      onClick={handleFocusOutside}
      role="button"
      tabIndex={0}
    >
      {children}
    </div>
  );
}

export default ModalOverlay;
