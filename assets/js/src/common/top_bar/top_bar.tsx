import React from 'react';
import { MailPoetLogo } from './mailpoet_logo';
import { BeamerIcon } from './beamer_icon';

type Props = {
  children?: React.ReactNode,
};

export const TopBar = ({
  children,
}: Props) => (
  <div className="top-bar">
    <div className="top-bar-logo">
      <MailPoetLogo />
    </div>
    <div className="top-bar-content">
      <div className="top-bar-children">
        {children}
      </div>
      <div className="top-bar-beamer">
        <BeamerIcon />
      </div>
    </div>
  </div>
);
