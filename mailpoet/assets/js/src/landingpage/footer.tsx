import { __ } from '@wordpress/i18n';
import { Button } from 'common';
import { Heading } from 'common/typography/heading/heading';
import { redirectToWelcomeWizard } from './util';

function Footer() {
  return (
    <div className="landing-footer">
      <div className="landing-footer-content mailpoet-content-center">
        <Heading level={4}>
          {' '}
          {__('Ready to start using MailPoet?', 'mailpoet')}{' '}
        </Heading>
        <Button onClick={redirectToWelcomeWizard}>
          {__('Begin setup', 'mailpoet')}
        </Button>
      </div>
    </div>
  );
}
Footer.displayName = 'Landingpage Footer';

export { Footer };
