import { ReactNode } from 'react';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { withFeatureAnnouncement } from 'announcements/with-feature-announcement';
import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { MailPoetLogoResponsive } from './mailpoet-logo-responsive';
import { BeamerIcon } from './beamer-icon';
import { ScreenOptionsFix } from './screen-options-fix';
import { withBoundary } from '../error-boundary';
import { MailPoet } from '../../mailpoet';

type Props = {
  children?: ReactNode;
  hasNews?: boolean;
  onBeamerClick?: () => void;
  logoWithLink?: boolean;
  hideScreenOptions?: boolean;
};

export function TopBar({
  children,
  hasNews,
  onBeamerClick,
  logoWithLink = true,
  hideScreenOptions = false,
}: Props) {
  const buttonClasses = classnames(
    'mailpoet-top-bar-beamer',
    hasNews ? 'mailpoet-top-bar-beamer-dot' : '',
  );
  return (
    <div className="mailpoet-top-bar">
      <MailPoetLogoResponsive withLink={logoWithLink} />
      <div className="mailpoet-top-bar-children">{children}</div>
      <div className="mailpoet-flex-grow" />
      {onBeamerClick && MailPoet.libs3rdPartyEnabled && (
        <div>
          <a
            role="button"
            onClick={onBeamerClick}
            className={buttonClasses}
            title={__('What’s new', 'mailpoet')}
            tabIndex={0}
            onKeyDown={(event) => {
              if (
                ['keydown', 'keypress'].includes(event.type) &&
                ['Enter', ' '].includes(event.key)
              ) {
                event.preventDefault();
                onBeamerClick();
              }
            }}
          >
            <BeamerIcon />
            <span>{__('Updates', 'mailpoet')}</span>
          </a>
          <span id="beamer-empty-element" />
        </div>
      )}
      <ScreenOptionsFix />
      {hideScreenOptions && <HideScreenOptions />}
    </div>
  );
}

TopBar.displayName = 'TopBar';
export const TopBarWithBeamer = withFeatureAnnouncement(withBoundary(TopBar));
