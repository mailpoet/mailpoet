import { MailPoet } from 'mailpoet';
import { Button } from 'common';
import { Heading } from 'common/typography/heading/heading';
import { redirectToWelcomeWizard } from './util';

function Footer() {
  return (
    <div className="mailpoet-content-center landing-footer">
      <Heading level={4}> {MailPoet.I18n.t('readyToUseMailPoet')} </Heading>
      <Button onClick={redirectToWelcomeWizard}>
        {' '}
        {MailPoet.I18n.t('beginSetup')}{' '}
      </Button>
    </div>
  );
}
Footer.displayName = 'Landingpage Footer';

export { Footer };
