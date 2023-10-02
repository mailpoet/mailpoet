import { useHistory } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { MailPoetLogo } from './mailpoet-logo';
import { MailPoetLogoMobile } from './mailpoet-logo-mobile';

type Props = {
  onClick?: () => void;
  withLink?: boolean;
};

export function MailPoetLogoResponsive({ onClick, withLink = true }: Props) {
  const history = useHistory();
  let logo;
  let onLogoClick = onClick;
  if (!onClick) {
    onLogoClick = () => history.push('/');
  }

  if (withLink) {
    logo = (
      <a
        role="button"
        className="mailpoet-top-bar-logo"
        title={__('Back to section root', 'mailpoet')}
        onClick={onLogoClick}
        tabIndex={0}
        onKeyDown={(event) => {
          if (
            ['keydown', 'keypress'].includes(event.type) &&
            ['Enter', ' '].includes(event.key)
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
    );
  } else {
    logo = (
      <div className="mailpoet-top-bar-logo">
        <div className="mailpoet-top-bar-logo-desktop">
          <MailPoetLogo />
        </div>
        <div className="mailpoet-top-bar-logo-mobile">
          <MailPoetLogoMobile />
        </div>
      </div>
    );
  }

  return logo;
}
