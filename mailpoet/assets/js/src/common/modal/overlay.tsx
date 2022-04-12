import {
  KeyboardEvent,
  MouseEvent,
  ReactNode,
  SyntheticEvent,
  useEffect,
  useRef,
} from 'react';
import classnames from 'classnames';
import { noop } from 'lodash';

const ESCAPE = 27;

type Props = {
  isDismissible?: boolean;
  shouldCloseOnEsc?: boolean;
  shouldCloseOnClickOutside?: boolean;
  onRequestClose?: (event: SyntheticEvent) => void;
  className?: string;
  children: ReactNode;
};

export function ModalOverlay({
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

  function onClose(event: SyntheticEvent) {
    if (onRequestClose) {
      onRequestClose(event);
    }
  }

  function handleFocusOutside(event: MouseEvent) {
    // filter only to clicks on overlay
    if (shouldCloseOnClickOutside && overlayRef.current === event.target) {
      onClose(event);
    }
  }

  function handleEscapeKeyDown(event: KeyboardEvent) {
    if (shouldCloseOnEsc) {
      event.stopPropagation();
      onClose(event);
    }
  }

  function handleKeyDown(event: KeyboardEvent) {
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
        className,
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
