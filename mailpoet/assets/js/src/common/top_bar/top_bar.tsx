import { ReactNode } from 'react';
import classnames from 'classnames';
import { t } from 'common/functions/t';
import { withFeatureAnnouncement } from 'announcements/with_feature_announcement';
import { MailPoetLogoResponsive } from './mailpoet_logo_responsive';
import { BeamerIcon } from './beamer_icon';
import { ScreenOptionsFix } from './screen_options_fix';
import { withBoundary } from '../error_boundary';

type Props = {
  children?: ReactNode;
  hasNews?: boolean;
  onBeamerClick?: () => void;
  logoWithLink?: boolean;
};

export function TopBar({
  children,
  hasNews,
  onBeamerClick,
  logoWithLink = true,
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
      {onBeamerClick && (
        <div>
          <a
            role="button"
            onClick={onBeamerClick}
            className={buttonClasses}
            title={t('whatsNew')}
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
            <span>{t('topBarUpdates')}</span>
          </a>
          <span id="beamer-empty-element" />
        </div>
      )}
      <ScreenOptionsFix />
    </div>
  );
}

TopBar.displayName = 'TopBar';
export const TopBarWithBeamer = withFeatureAnnouncement(withBoundary(TopBar));
