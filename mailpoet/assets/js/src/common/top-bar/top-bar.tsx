import { ReactNode } from 'react';
import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { MailPoetLogoResponsive } from './mailpoet-logo-responsive';
import { ScreenOptionsFix } from './screen-options-fix';
import { withBoundary } from '../error-boundary';

type Props = {
  children?: ReactNode;
  logoWithLink?: boolean;
  hideScreenOptions?: boolean;
};

export function TopBar({
  children,
  logoWithLink = true,
  hideScreenOptions = false,
}: Props) {
  return (
    <div className="mailpoet-top-bar">
      <MailPoetLogoResponsive withLink={logoWithLink} />
      <div className="mailpoet-top-bar-children">{children}</div>
      <div className="mailpoet-flex-grow" />
      <ScreenOptionsFix />
      {hideScreenOptions && <HideScreenOptions />}
    </div>
  );
}

TopBar.displayName = 'TopBar';
export const TopBarWithBoundary = withBoundary(TopBar);
