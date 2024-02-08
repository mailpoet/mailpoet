import { __ } from '@wordpress/i18n';
import { Button } from '../common';
import { redirectToWelcomeWizard } from './util';

export function WelcomeWizardButton() {
  const savedStep = window.mailpoet_welcome_wizard_current_step;
  const userHasStarted =
    typeof savedStep === 'string' && savedStep.startsWith('/steps');

  return (
    <Button onClick={redirectToWelcomeWizard} dimension="hero">
      {userHasStarted
        ? __('Continue setup', 'mailpoet')
        : __('Begin setup', 'mailpoet')}
    </Button>
  );
}
