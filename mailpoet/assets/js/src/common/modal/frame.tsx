import { ReactNode } from 'react';
import classnames from 'classnames';

type Props = {
  fullScreen?: boolean;
  className?: string;
  ariaLabel?: string;
  ariaLabelledBy?: string;
  children: ReactNode;
};

export function ModalFrame({
  fullScreen = false,
  className = '',
  ariaLabel = undefined,
  ariaLabelledBy = undefined,
  children,
}: Props) {
  return (
    <div
      className={classnames(
        'mailpoet-modal-frame',
        { 'mailpoet-modal-full-screen': fullScreen },
        className,
      )}
      role="dialog"
      aria-label={ariaLabel}
      aria-labelledby={ariaLabelledBy}
      tabIndex={-1}
    >
      {children}
    </div>
  );
}
