import React from 'react';
import classNames from 'classnames';
import { withFeatureAnnouncement } from 'announcements/with_feature_announcement';
import { MailPoetLogo } from './mailpoet_logo';
import { BeamerIcon } from './beamer_icon';
import MailPoet from '../../mailpoet';

type Props = {
  children?: React.ReactNode
  hasNews: boolean
  onBeamerClick: () => void
  onLogoClick: () => void
};

export const TopBar = ({
  children,
  hasNews,
  onBeamerClick,
  onLogoClick,
}: Props) => {
  const buttonClasses = classNames(
    'button top-bar-beamer',
    hasNews ? 'top-bar-beamer-dot' : ''
  );
  return (
    <div className="top-bar">
      <a
        role="button"
        className="top-bar-logo"
        onClick={onLogoClick}
        tabIndex={0}
        onKeyDown={(event) => {
          if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
          ) {
            event.preventDefault();
            onLogoClick();
          }
        }}
      >
        <MailPoetLogo />
      </a>
      <div className="top-bar-content">
        <div className="top-bar-children">
          {children}
        </div>
        <button
          type="button"
          onClick={onBeamerClick}
          className={buttonClasses}
          title={MailPoet.I18n.t('whatsNew')}
        >
          <BeamerIcon />
        </button>
      </div>
    </div>
  );
};

export const TopBarWithBeamer = withFeatureAnnouncement(TopBar);
