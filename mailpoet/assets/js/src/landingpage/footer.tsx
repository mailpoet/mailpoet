import { MailPoet } from 'mailpoet';
import { Button } from 'common';
import { Heading } from 'common/typography/heading/heading';

function Footer(): JSX.Element {
  return (
    <div className="mailpoet-content-center landing-footer">
      <Heading level={4}> {MailPoet.I18n.t('readyToUseMailPoet')} </Heading>
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
Footer.displayName = 'Landingpage Footer';

export { Footer };
