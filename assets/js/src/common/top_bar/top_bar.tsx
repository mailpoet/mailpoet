import React from 'react';
import classNames from 'classnames';
import t from 'common/functions/t';
import { withFeatureAnnouncement } from 'announcements/with_feature_announcement';
import { MailPoetLogo } from './mailpoet_logo';
import { MailPoetLogoMobile } from './mailpoet_logo_mobile';
import { BeamerIcon } from './beamer_icon';
import { ScreenOptionsFix } from './screen_options_fix';

type Props = {
  children?: React.ReactNode
  hasNews?: boolean
  onBeamerClick?: () => void
  onLogoClick: () => void
};

export const TopBar = ({
  children,
  hasNews,
  onBeamerClick,
  onLogoClick,
}: Props) => {
  const buttonClasses = classNames(
    'mailpoet-top-bar-beamer',
    hasNews ? 'mailpoet-top-bar-beamer-dot' : ''
  );
  return (
    <div className="mailpoet-top-bar">
      <a
        role="button"
        className="mailpoet-top-bar-logo"
        title={t('topBarLogoTitle')}
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
        <div className="mailpoet-top-bar-logo-desktop">
          <MailPoetLogo />
        </div>
        <div className="mailpoet-top-bar-logo-mobile">
          <MailPoetLogoMobile />
        </div>
      </a>
      <div className="mailpoet-top-bar-content">
        <div className="mailpoet-top-bar-children">
          {children}
        </div>
        {onBeamerClick && (
          <>
            <a
              role="button"
              onClick={onBeamerClick}
              className={buttonClasses}
              title={t('whatsNew')}
              tabIndex={0}
              onKeyDown={(event) => {
                if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
                ) {
                  event.preventDefault();
                  onBeamerClick();
                }
              }}
            >
              <BeamerIcon />
            </a>
            <span id="beamer-empty-element" />
          </>
        )}
      </div>
      <ScreenOptionsFix />
    </div>
  );
};

export const TopBarWithBeamer = withFeatureAnnouncement(TopBar);
