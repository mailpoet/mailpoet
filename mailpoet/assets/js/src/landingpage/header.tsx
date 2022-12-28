import { MailPoet } from 'mailpoet';
import { Button } from 'common';
import { Heading } from 'common/typography/heading/heading';

function Header(): JSX.Element {
  return (
    <div className="mailpoet-content-center">
      <Heading level={1}>
        {' '}
        {MailPoet.I18n.t('betterEmailWithoutLeavingWordPress')}{' '}
      </Heading>
      <Heading level={3}>
        {' '}
        {MailPoet.I18n.t('startingOutOrEstablished')}{' '}
      </Heading>
      <Button
        onClick={() => {
          window.location.href = window.mailpoet_welcome_wizard_url;
        }}
      >
        {' '}
        {MailPoet.I18n.t('beginSetup')}{' '}
      </Button>
    </div>
  );
}
Header.displayName = 'Landingpage Header';
export { Header };
