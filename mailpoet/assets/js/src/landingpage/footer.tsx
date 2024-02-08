import { __ } from '@wordpress/i18n';
import { Heading } from 'common/typography/heading/heading';
import { WelcomeWizardButton } from './welcome-wizard-button';

function Footer() {
  return (
    <section className="landing-footer">
      <div className="landing-footer-content mailpoet-content-center">
        <Heading level={4}>
          {' '}
          {__('Ready to start using MailPoet?', 'mailpoet')}{' '}
        </Heading>
        <WelcomeWizardButton />
      </div>
    </section>
  );
}
Footer.displayName = 'Landingpage Footer';

export { Footer };
