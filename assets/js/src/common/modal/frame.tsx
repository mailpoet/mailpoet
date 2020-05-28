import React from 'react';
import classnames from 'classnames';

type Props = {
  fullScreen?: boolean,
  role?: string,
  className?: string,
  children: React.ReactNode,
};

function ModalFrame({
  children,
  className,
  role,
  fullScreen,
}: Props) {
  return (
    <div
      className={classnames(
        'mailpoet-modal-frame',
        { 'mailpoet-modal-full-screen': fullScreen },
        className
      )}
      role={role}
      tabIndex={-1}
    >
      {children}
    </div>
  );
}

ModalFrame.defaultProps = {
  role: 'dialog',
  fullScreen: false,
  className: '',
};

export default ModalFrame;
