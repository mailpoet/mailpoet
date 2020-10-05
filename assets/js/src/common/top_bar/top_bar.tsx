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
};

export const TopBar = ({
  children,
  hasNews,
  onBeamerClick,
}: Props) => {
  const buttonClasses = classNames(
    'button top-bar-beamer',
    hasNews ? 'top-bar-beamer-dot' : ''
  );
  return (
    <div className="top-bar">
      <div className="top-bar-logo">
        <MailPoetLogo />
      </div>
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
