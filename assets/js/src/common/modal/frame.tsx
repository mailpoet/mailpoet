import React from 'react';
import classnames from 'classnames';

type Props = {
  fullScreen?: boolean,
  className?: string,
  children: React.ReactNode,
};

function ModalFrame({
  children,
  className = '',
  fullScreen = false,
}: Props) {
  return (
    <div
      className={classnames(
        'mailpoet-modal-frame',
        { 'mailpoet-modal-full-screen': fullScreen },
        className
      )}
      role="dialog"
      tabIndex={-1}
    >
      {children}
    </div>
  );
}

export default ModalFrame;
