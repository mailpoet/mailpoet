import { ReactNode, useId } from 'react';
import { createPortal } from 'react-dom';
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';

import { ModalFrame } from './frame';
import { ModalHeader } from './header';
import { ModalOverlay } from './overlay';
import { modalCloseIcon } from './close-icon';

type Props = {
  title?: string;
  isDismissible?: boolean;
  shouldCloseOnEsc?: boolean;
  shouldCloseOnClickOutside?: boolean;
  onRequestClose?: () => void;
  fullScreen?: boolean;
  contentClassName?: string;
  overlayClassName?: string;
  ariaLabel?: string;
  closeButtonLabel?: string;
  children: ReactNode;
};

export function Modal({
  title = null,
  isDismissible = true,
  shouldCloseOnEsc = true,
  shouldCloseOnClickOutside = true,
  onRequestClose = noop,
  fullScreen = false,
  contentClassName = '',
  overlayClassName = '',
  ariaLabel = undefined,
  closeButtonLabel = __('Close modal', 'mailpoet'),
  children,
}: Props) {
  const generatedTitleId = useId();
  const titleId = title
    ? `mailpoet-modal-title-${generatedTitleId}`
    : undefined;

  return createPortal(
    <ModalOverlay
      isDismissible={isDismissible}
      onRequestClose={onRequestClose}
      shouldCloseOnEsc={shouldCloseOnEsc}
      shouldCloseOnClickOutside={shouldCloseOnClickOutside}
      className={overlayClassName}
    >
      <ModalFrame
        className={contentClassName}
        fullScreen={fullScreen}
        ariaLabel={ariaLabel}
        ariaLabelledBy={ariaLabel ? undefined : titleId}
      >
        {title && <ModalHeader id={titleId} title={title} />}
        {isDismissible && (
          <button
            type="button"
            onClick={onRequestClose}
            className="mailpoet-modal-close"
            data-automation-id="mailpoet-modal-close"
            aria-label={closeButtonLabel}
          >
            {modalCloseIcon}
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
