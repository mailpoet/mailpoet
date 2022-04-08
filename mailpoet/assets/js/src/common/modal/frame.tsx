import { ReactNode } from 'react';
import classnames from 'classnames';

type Props = {
  fullScreen?: boolean;
  className?: string;
  children: ReactNode;
};

function ModalFrame({ fullScreen = false, className = '', children }: Props) {
  return (
    <div
      className={classnames(
        'mailpoet-modal-frame',
        { 'mailpoet-modal-full-screen': fullScreen },
        className,
      )}
      role="dialog"
      tabIndex={-1}
    >
      {children}
    </div>
  );
}

export default ModalFrame;
