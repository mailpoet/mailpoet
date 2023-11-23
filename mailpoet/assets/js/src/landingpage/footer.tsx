import { __ } from '@wordpress/i18n';
import { Button } from 'common';
import { Heading } from 'common/typography/heading/heading';
import { redirectToWelcomeWizard } from './util';

function Footer() {
  return (
    <section className="landing-footer">
      <div className="landing-footer-content mailpoet-content-center">
        <Heading level={4}>
          {' '}
          {__('Ready to start using MailPoet?', 'mailpoet')}{' '}
        </Heading>
        <Button onClick={redirectToWelcomeWizard} dimension="hero">
          {__('Begin setup', 'mailpoet')}
        </Button>
      </div>
    </section>
  );
}
Footer.displayName = 'Landingpage Footer';

export { Footer };
