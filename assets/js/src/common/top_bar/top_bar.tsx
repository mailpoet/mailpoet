import React from 'react';
import { MailPoetLogo } from './mailpoet_logo';
import { BeamerIcon } from './beamer_icon';

export const TopBar = () => (
  <div className="top-bar">
    <div className="top-bar-logo">
      <MailPoetLogo />
    </div>
    <div>
      <div className="top-bar-beamer">
        <BeamerIcon />
      </div>
    </div>
  </div>
);
