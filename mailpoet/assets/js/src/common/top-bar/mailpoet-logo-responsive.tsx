import { useNavigate } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { MailPoetLogo } from './mailpoet-logo';
import { MailPoetLogoMobile } from './mailpoet-logo-mobile';

type Props = {
  onClick?: () => void;
  withLink?: boolean;
};

export function MailPoetLogoResponsive({ onClick, withLink = true }: Props) {
  let navigateFallback;
  // The component is used on many places and not all of them have access to the router.
  // We try to obtain the navigate function and if it fails we fallback to redirect to homepage.
  try {
    const navigate = useNavigate();
    navigateFallback = () => navigate('/');
  } catch (e) {
    navigateFallback = () => {
      window.location.href = `admin.php?page=${
        new URL(window.location.href).searchParams.get('page') ??
        MailPoet.mainPageSlug
      }`;
    };
  }

  let logo;
  let onLogoClick = onClick;
  if (!onClick) {
    onLogoClick = navigateFallback;
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
