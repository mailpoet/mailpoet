import { useHistory } from 'react-router-dom';
import { t } from 'common/functions/t';
import { MailPoetLogo } from './mailpoet_logo';
import { MailPoetLogoMobile } from './mailpoet_logo_mobile';

type Props = {
  onClick?: () => void;
};
export function MailPoetLogoResponsive({ onClick }: Props) {
  const history = useHistory();
  let onLogoClick = onClick;
  if (!onClick) {
    onLogoClick = () => history.push('/');
  }
  return (
    <a
      role="button"
      className="mailpoet-top-bar-logo"
      title={t('topBarLogoTitle')}
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
}
